<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service;

use Assert\Assertion;
use ElevenLabs\Api\Decoder\DecoderUtils;
use ElevenLabs\Api\Definition\Parameter;
use ElevenLabs\Api\Definition\Parameters;
use ElevenLabs\Api\Definition\RequestDefinition;
use ElevenLabs\Api\Definition\ResponseDefinition;
use ElevenLabs\Api\Schema;
use ElevenLabs\Api\Service\Exception\ConstraintViolations;
use ElevenLabs\Api\Service\Exception\RequestViolations;
use ElevenLabs\Api\Service\Exception\ResponseViolations;
use ElevenLabs\Api\Service\Resource\ErrorInterface;
use ElevenLabs\Api\Service\Resource\Item;
use ElevenLabs\Api\Service\Resource\ResourceInterface;
use ElevenLabs\Api\Validator\MessageValidator;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Message\MessageFactory;
use Http\Message\UriFactory;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Rize\UriTemplate;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class ApiService.
 */
class ApiService
{
    const DEFAULT_CONFIG = [
        // override the scheme and host provided in the API schema by a baseUri (ex:https://domain.com)
        'baseUri' => null,
        // validate request
        'validateRequest' => true,
        // validate response
        'validateResponse' => false,
        // return response instead of a denormalized object
        'returnResponse' => false,
    ];

    /**
     * @var UriInterface
     */
    private $baseUri;

    /**
     * @var Schema
     */
    private $schema;

    /**
     * @var MessageValidator
     */
    private $messageValidator;

    /**
     * @var HttpAsyncClient|HttpClient
     */
    private $client;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * @var UriTemplate
     */
    private $uriTemplate;

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
    private $config;

    public function __construct(
        UriFactory $uriFactory,
        UriTemplate $uriTemplate,
        HttpClient $client,
        MessageFactory $messageFactory,
        Schema $schema,
        MessageValidator $messageValidator,
        SerializerInterface $serializer,
        array $config = []
    ) {
        $this->uriFactory = $uriFactory;
        $this->uriTemplate = $uriTemplate;
        $this->schema = $schema;
        $this->messageValidator = $messageValidator;
        $this->client = $client;
        $this->messageFactory = $messageFactory;
        $this->serializer = $serializer;
        $this->config = $this->getConfig($config);
        $this->baseUri = $this->getBaseUri();
    }

    public function call(string $operationId, array $params = [])
    {
        $requestDefinition = $this->schema->getRequestDefinition($operationId);
        $request = $this->createRequestFromDefinition($requestDefinition, $params);
        $this->validateRequest($request, $requestDefinition);

        $response = $this->client->sendRequest($request);
        $this->validateResponse($response, $requestDefinition);

        return $this->getDataFromResponse(
            $response,
            $requestDefinition->getResponseDefinition(
                $response->getStatusCode()
            ),
            $request
        );
    }

    public function callAsync(string $operationId, array $params = []): Promise
    {
        if (!$this->client instanceof HttpAsyncClient) {
            throw new \RuntimeException(
                sprintf(
                    '"%s" does not support async request',
                    get_class($this->client)
                )
            );
        }

        $requestDefinition = $this->schema->getRequestDefinition($operationId);
        $request = $this->createRequestFromDefinition($requestDefinition, $params);
        $promise = $this->client->sendAsyncRequest($request);

        return $promise->then(
            function (ResponseInterface $response) use ($request, $requestDefinition) {

                return $this->getDataFromResponse(
                    $response,
                    $requestDefinition->getResponseDefinition(
                        $response->getStatusCode()
                    ),
                    $request
                );
            }
        );
    }

    public function getSchema(): Schema
    {
        return $this->schema;
    }

    public static function buildQuery(array $params): array
    {
        foreach ($params as $key => $param) {
            if (is_array($param)) {
                foreach ($param as $k => $v) {
                    $params[$key.'['.$k.']'] = $v;
                }
                unset($params[$key]);
            }
            if (false !== stripos($key, '_')) {
                $params[str_replace('_', '.', $key)] = $param;
                unset($params[$key]);
            }
        }

        return $params;
    }

    private function getBaseUri(): UriInterface
    {
        // Create a base uri from the API Schema
        if (null === $this->config['baseUri']) {
            $schemes = $this->schema->getSchemes();
            if (empty($schemes)) {
                throw new \LogicException('You need to provide at least on scheme in your API Schema');
            }

            $scheme = null;
            foreach ($this->schema->getSchemes() as $candidate) {
                // Always prefer https
                if ('https' === $candidate) {
                    $scheme = 'https';
                }
                if (null === $scheme && 'http' === $candidate) {
                    $scheme = 'http';
                }
            }

            if (null === $scheme) {
                throw new \RuntimeException('Cannot choose a proper scheme from the API Schema. Supported: https, http');
            }

            $host = $this->schema->getHost();
            if ('' === $host) {
                throw new \LogicException('The host in the API Schema should not be null');
            }

            return $this->uriFactory->createUri($scheme.'://'.$host);
        } else {
            return $this->uriFactory->createUri($this->config['baseUri']);
        }
    }

    private function getConfig(array $config): array
    {
        $config = array_merge(self::DEFAULT_CONFIG, $config);
        Assertion::boolean($config['returnResponse']);
        Assertion::boolean($config['validateRequest']);
        Assertion::boolean($config['validateResponse']);
        Assertion::nullOrString($config['baseUri']);

        return array_intersect_key($config, self::DEFAULT_CONFIG);
    }

    private function createRequestFromDefinition(RequestDefinition $definition, array $params): RequestInterface
    {
        $contentType = $definition->getContentTypes()[0] ?? 'application/json';
        $requestParameters = $definition->getRequestParameters();
        list($path, $query, $headers, $body, $formData) = $this->getDefaultValues($requestParameters);
        $headers = array_merge(
            $headers,
            ['Content-Type' => $contentType, 'Accept' => $definition->getAccepts()[0] ?? 'application/json']
        );

        foreach ($params as $name => $value) {
            $requestParameter = $requestParameters->getByName($name);
            if (null === $requestParameter) {
                throw new \InvalidArgumentException(sprintf('%s is not a defined request parameter', $name));
            }

            switch ($requestParameter->getLocation()) {
                case 'path':
                    $path[$name] = $value;
                    break;
                case 'query':
                    $query[$name] = $value;
                    break;
                case 'header':
                    $headers[$name] = $value;
                    break;
                case 'body':
                    $body = $this->serializeRequestBody($value, $contentType);
                    break;
                case 'formData':
                    $formData[$name] = sprintf('%s=%s', $name, $value);
                    break;
            }
        }

        if (!empty($formData)) {
            $body = implode('&', $formData);
        }

        $request = $this->messageFactory->createRequest(
            $definition->getMethod(),
            $this->buildRequestUri($definition->getPathTemplate(), $path, $query),
            $headers,
            $body
        );

        return $request;
    }

    private function getDefaultValues(Parameters $requestParameters): array
    {
        $path = [];
        $query = [];
        $headers = [];
        $body = null;
        $formData = [];

        /** @var Parameter $parameter */
        foreach ($requestParameters->getIterator() as $name => $parameter) {
            if (isset($parameter->getSchema()->default)) {
                $value = $parameter->getSchema()->default;
                switch ($parameter->getLocation()) {
                    case 'path':
                        $path[$name] = $value;
                        break;
                    case 'query':
                        $query[$name] = $value;
                        break;
                    case 'header':
                        $headers[$name] = $value;
                        break;
                    case 'formData':
                        $formData[$name] = sprintf('%s=%s', $name, $value);
                        break;
                    case 'body':
                        $body = $this->serializeRequestBody($value, $contentType);
                        break;
                }
            }
        }

        return [$path, $query, $headers, $body, $formData];
    }

    private function buildRequestUri(string $pathTemplate, array $pathParameters, array $queryParameters): UriInterface
    {
        $path = $this->uriTemplate->expand($pathTemplate, $pathParameters);
        $query = http_build_query($queryParameters);

        return $this->baseUri->withPath($path)->withQuery($query);
    }

    private function serializeRequestBody(array $decodedBody, string $contentType): string
    {
        return $this->serializer->serialize(
            $decodedBody,
            DecoderUtils::extractFormatFromContentType($contentType)
        );
    }

    private function getDataFromResponse(ResponseInterface $response, ResponseDefinition $definition, RequestInterface $request)
    {
        if (true === $this->config['returnResponse']) {
            return $response;
        }

        // @todo Find a better way to handle responses with a body definition
        if (!$definition->hasBodySchema()) {
            return new Item([], $request->getHeaders(), []);
        }
        $statusCode = $response->getStatusCode();

        return $this->serializer->deserialize(
            (string) $response->getBody(),
            $statusCode >= 400 && $statusCode <= 599 ? ErrorInterface::class : ResourceInterface::class,
            DecoderUtils::extractFormatFromContentType($response->getHeaderLine('Content-Type')),
            [
                'response' => $response,
                'responseDefinition' => $definition,
                'request' => $request,
            ]
        );
    }

    private function validateRequest(RequestInterface $request, RequestDefinition $definition)
    {
        if (false === $this->config['validateRequest']) {
            return;
        }

        $this->messageValidator->validateRequest($request, $definition);
        if ($this->messageValidator->hasViolations()) {
            throw new RequestViolations(
                $this->messageValidator->getViolations()
            );
        }
    }

    private function validateResponse(ResponseInterface $response, RequestDefinition $definition)
    {
        if (false === $this->config['validateResponse']) {
            return;
        }

        $this->messageValidator->validateResponse($response, $definition);
        if ($this->messageValidator->hasViolations()) {
            throw new ResponseViolations(
                $this->messageValidator->getViolations()
            );
        }
    }
}
