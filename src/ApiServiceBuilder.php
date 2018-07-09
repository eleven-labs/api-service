<?php

namespace ElevenLabs\Api\Service;

use ElevenLabs\Api\Decoder\Adapter\SymfonyDecoderAdapter;
use ElevenLabs\Api\Factory\CachedSchemaFactoryDecorator;
use ElevenLabs\Api\Factory\SwaggerSchemaFactory;
use ElevenLabs\Api\Service\Denormalizer\ResourceDenormalizer;
use ElevenLabs\Api\Service\Pagination\PaginationProvider;
use ElevenLabs\Api\Validator\MessageValidator;
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
    private $config = [];
    private $paginationProvider = null;

    /**
     * @return ApiServiceBuilder
     */
    public static function create()
    {
        return new static();
    }

    /**
     * @param CacheItemPoolInterface $cache
     *
     * @return $this
     */
    public function withCacheProvider(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * @param HttpClient $httpClient
     *
     * @return $this
     */
    public function withHttpClient(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    /**
     * @param MessageFactory $messageFactory
     *
     * @return $this
     */
    public function withMessageFactory(MessageFactory $messageFactory)
    {
        $this->messageFactory = $messageFactory;

        return $this;
    }

    /**
     * @param UriFactory $uriFactory
     *
     * @return $this
     */
    public function withUriFactory(UriFactory $uriFactory)
    {
        $this->uriFactory = $uriFactory;

        return $this;
    }

    /**
     * @param SerializerInterface $serializer
     *
     * @return $this
     */
    public function withSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;

        return $this;
    }

    /**
     * @param EncoderInterface $encoder
     *
     * @return $this
     */
    public function withEncoder(EncoderInterface $encoder)
    {
        $this->encoders[] = $encoder;

        return $this;
    }

    /**
     * @param NormalizerInterface $normalizer
     *
     * @return $this
     */
    public function withDenormalizer(NormalizerInterface $normalizer)
    {
        $this->denormalizers[] = $normalizer;

        return $this;
    }

    /**
     * @param PaginationProvider $paginationProvider
     */
    public function withPaginationProvider(PaginationProvider $paginationProvider)
    {
        $this->paginationProvider = $paginationProvider;
    }

    /**
     * @param string $baseUri
     *
     * @return $this
     */
    public function withBaseUri($baseUri)
    {
        $this->config['baseUri'] = $baseUri;

        return $this;
    }

    /**
     * @return $this
     */
    public function disableRequestValidation()
    {
        $this->config['validateRequest'] = false;

        return $this;
    }

    /**
     * @return $this
     */
    public function enableResponseValidation()
    {
        $this->config['validateResponse'] = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function returnResponse()
    {
        $this->config['returnResponse'] = true;

        return $this;
    }

    /**
     * @param string $schemaPath
     *
     * @return ApiService
     */
    public function build($schemaPath)
    {
        // Build serializer
        if ($this->serializer === null) {
            if (empty($this->encoders)) {
                $this->encoders = [new JsonEncoder(), new XmlEncoder()];
            }

            if (empty($this->denormalizers)) {
                $this->denormalizers[] = new ResourceDenormalizer($this->paginationProvider);
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

        if ($this->httpClient === null) {
            $this->httpClient = HttpClientDiscovery::find();
        }

        $schemaFactory = new SwaggerSchemaFactory();
        if ($this->cache !== null) {
            $schemaFactory = new CachedSchemaFactoryDecorator(
                $this->cache,
                $schemaFactory
            );
        }

        $this->schema = $schemaFactory->createSchema($schemaPath);

        if (!isset($this->requestValidator)) {
            $this->requestValidator = new MessageValidator(
                new Validator(),
                new SymfonyDecoderAdapter(new ChainDecoder($this->encoders))
            );
        }

        return new ApiService(
            $this->uriFactory,
            new UriTemplate(),
            $this->httpClient,
            $this->messageFactory,
            $this->schema,
            $this->requestValidator,
            $this->serializer,
            $this->config
        );
    }
}
