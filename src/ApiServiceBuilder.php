<?php
namespace ElevenLabs\Api\Service;

use ElevenLabs\Api\Decoder\Adapter\SymfonyDecoderAdapter;
use ElevenLabs\Api\Factory\SwaggerSchemaFactory;
use ElevenLabs\Api\Schema;
use ElevenLabs\Api\Service\Denormalizer\ResourceDenormalizer;
use ElevenLabs\Api\Service\Schema\Factory\CachedSchemaFactoryDecorator;
use ElevenLabs\Api\Validator\RequestValidator;
use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Discovery\UriFactoryDiscovery;
use Http\Message\MessageFactory;
use Http\Message\UriFactory;
use JsonSchema\Validator;
use Psr\Cache\CacheItemPoolInterface;
use Rize\UriTemplate;
use Symfony\Component\Serializer\Encoder\ChainDecoder;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Builder for ApiService instances
 */
class ApiServiceBuilder
{
    private $httpClient;
    private $messageFactory;
    private $uriFactory;
    private $serializer;
    private $denormalizers = [];
    private $encoders = [];
    private $schema;
    private $cache;
    private $debug = false;

    public static function create()
    {
        return new static();
    }

    public function setDebug($bool)
    {
        $this->debug = (boolean) $bool;

        return $this;
    }

    public function withCacheProvider(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    public function withHttpClient(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    public function withMessageFactory(MessageFactory $messageFactory)
    {
        $this->messageFactory = $messageFactory;

        return $this;
    }

    public function withUriFactory(UriFactory $uriFactory)
    {
        $this->uriFactory = $uriFactory;

        return $this;
    }

    public function withSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;

        return $this;
    }

    public function withEncoder(EncoderInterface $encoder)
    {
        $this->encoders[] = $encoder;

        return $this;
    }

    public function withDenormalizer(NormalizerInterface $normalizer)
    {
        $this->denormalizers[] = $normalizer;

        return $this;
    }

    public function withSchema(Schema $schema)
    {
        $this->schema = $schema;

        return $this;
    }

    public function build($baseUri, $schemaPath)
    {
        // Build serializer
        if ($this->serializer === null) {
            if (empty($this->encoders)) {
                $this->encoders = [new JsonEncoder(), new XmlEncoder()];
            }
            if (empty($this->denormalizers)) {
                $this->denormalizers[] = new ResourceDenormalizer();
            }
            $this->serializer = new Serializer(
                $this->denormalizers,
                $this->encoders
            );
        }

        if ($this->uriFactory === null) {
            $this->uriFactory = UriFactoryDiscovery::find();
        }

        if ($this->messageFactory === null) {
            $this->messageFactory = MessageFactoryDiscovery::find();
        }

        if($this->httpClient === null) {
            $this->httpClient = HttpClientDiscovery::find();
        }

        $schemaFactory = new SwaggerSchemaFactory();
        if ($this->cache !== null) {
            $schemaFactory = new CachedSchemaFactoryDecorator(
                $this->cache,
                $schemaFactory
            );
            // Disable the cache A.S.A.P. in debug mode
            if ($this->debug === true) {
                $this->cache->expiresAt(new \DateTime());
            }
        }

        $this->schema = $schemaFactory->createSchema($schemaPath);

        if (!isset($this->requestValidator)) {
            $this->requestValidator = new RequestValidator(
                $this->schema,
                new Validator(),
                new SymfonyDecoderAdapter(new ChainDecoder($this->encoders))
            );
        }

        return new ApiService(
            $this->uriFactory->createUri($baseUri),
            new UriTemplate(),
            $this->httpClient,
            $this->messageFactory,
            $this->schema,
            $this->requestValidator,
            $this->serializer
        );
    }
}