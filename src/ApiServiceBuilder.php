<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service;

use ElevenLabs\Api\Decoder\Adapter\SymfonyDecoderAdapter;
use ElevenLabs\Api\Factory\CachedSchemaFactoryDecorator;
use ElevenLabs\Api\Factory\SwaggerSchemaFactory;
use ElevenLabs\Api\Schema;
use ElevenLabs\Api\Service\Denormalizer\ResourceDenormalizer;
use ElevenLabs\Api\Service\Pagination\Provider\PaginationProviderInterface;
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
 *
 * Class ApiServiceBuilder.
 */
class ApiServiceBuilder
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * @var UriFactory
     */
    private $uriFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var array
     */
    private $denormalizers = [];

    /**
     * @var array
     */
    private $encoders = [];

    /**
     * @var Schema
     */
    private $schema;

    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var array
     */
    private $config = [];

    /**
     * @var PaginationProviderInterface|null
     */
    private $paginationProvider = null;

    /**
     * @var MessageValidator
     */
    private $requestValidator;

    /**
     * @return ApiServiceBuilder
     */
    public static function create(): ApiServiceBuilder
    {
        return new static();
    }

    /**
     * @param CacheItemPoolInterface $cache
     *
     * @return $this
     */
    public function withCacheProvider(CacheItemPoolInterface $cache): self
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * @param HttpClient $httpClient
     *
     * @return $this
     */
    public function withHttpClient(HttpClient $httpClient): self
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    /**
     * @param MessageFactory $messageFactory
     *
     * @return $this
     */
    public function withMessageFactory(MessageFactory $messageFactory): self
    {
        $this->messageFactory = $messageFactory;

        return $this;
    }

    /**
     * @param UriFactory $uriFactory
     *
     * @return $this
     */
    public function withUriFactory(UriFactory $uriFactory): self
    {
        $this->uriFactory = $uriFactory;

        return $this;
    }

    /**
     * @param SerializerInterface $serializer
     *
     * @return $this
     */
    public function withSerializer(SerializerInterface $serializer): self
    {
        $this->serializer = $serializer;

        return $this;
    }

    /**
     * @param EncoderInterface $encoder
     *
     * @return $this
     */
    public function withEncoder(EncoderInterface $encoder): self
    {
        $this->encoders[] = $encoder;

        return $this;
    }

    /**
     * @param NormalizerInterface $normalizer
     *
     * @return $this
     */
    public function withDenormalizer(NormalizerInterface $normalizer): self
    {
        $this->denormalizers[] = $normalizer;

        return $this;
    }

    /**
     * @param PaginationProviderInterface $paginationProvider
     *
     * @return $this
     */
    public function withPaginationProvider(PaginationProviderInterface $paginationProvider): self
    {
        $this->paginationProvider = $paginationProvider;

        return $this;
    }

    /**
     * @param string $baseUri
     *
     * @return $this
     */
    public function withBaseUri(string $baseUri): self
    {
        $this->config['baseUri'] = $baseUri;

        return $this;
    }

    /**
     * @return $this
     */
    public function disableRequestValidation(): self
    {
        $this->config['validateRequest'] = false;

        return $this;
    }

    /**
     * @return $this
     */
    public function enableResponseValidation(): self
    {
        $this->config['validateResponse'] = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function returnResponse(): self
    {
        $this->config['returnResponse'] = true;

        return $this;
    }

    /**
     * @param string $schemaPath
     *
     * @throws \Assert\AssertionFailedException
     *
     * @return ApiService
     */
    public function build(string $schemaPath): ApiService
    {
        // Build serializer
        if (null === $this->serializer) {
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

        if (null === $this->uriFactory) {
            $this->uriFactory = UriFactoryDiscovery::find();
        }

        if (null === $this->messageFactory) {
            $this->messageFactory = MessageFactoryDiscovery::find();
        }

        if (null === $this->httpClient) {
            $this->httpClient = HttpClientDiscovery::find();
        }

        $schemaFactory = new SwaggerSchemaFactory();
        if (null !== $this->cache) {
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
