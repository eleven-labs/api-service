<?php
namespace ElevenLabs\Api\Service;

use Assert\Assertion;
use ElevenLabs\Api\Decoder\DecoderUtils;
use ElevenLabs\Api\Definition\RequestDefinition;
use ElevenLabs\Api\Definition\ResponseDefinition;
use ElevenLabs\Api\Schema;
use ElevenLabs\Api\Service\Exception\ConstraintViolations;
use ElevenLabs\Api\Service\Exception\RequestViolations;
use ElevenLabs\Api\Service\Exception\ResponseViolations;
use ElevenLabs\Api\Service\Resource\Resource;
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
 * A client that provide API service commands (pretty much like Guzzle)
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
        'returnResponse' => false
    ];

    /** @var UriInterface */
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

    /**
     * @param UriFactory $uriFactory The BaseUri of your API
     * @param UriTemplate $uriTemplate Used to expand Uri pattern in the API definition
     * @param HttpClient $client An HTTP client
     * @param MessageFactory $messageFactory
     * @param Schema $schema
     * @param MessageValidator $messageValidator
     * @param SerializerInterface $serializer
     * @param array $config
     */
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

    /**
     * Make a synchronous call to the API
     *
     * @param string $operationId The name of your operation as described in the API Schema
     * @param array $params An array of request parameters
     *
     * @return mixed
     */
    public function call($operationId, array $params = [])
    {
        $requestDefinition = $this->schema->getRequestDefinition($operationId);
        $request = $this->createRequestFromDefinition($requestDefinition, $params);
        $this->validateRequest($request, $requestDefinition);

        $response =  $this->client->sendRequest($request);
        $this->validateResponse($response, $requestDefinition);

        $data = $this->getDataFromResponse(
            $response,
            $requestDefinition->getResponseDefinition(
                $response->getStatusCode()
            ),
            $request
        );

        return $data;
    }

    /**
     * Make an asynchronous call to the API
     *
     * @param string $operationId The name of your operation as described in the API Schema
     * @param array $params An array of request parameters
     *
     * @return Promise
     */
    public function callAsync($operationId, array $params = [])
    {
        if (! $this->client instanceof HttpAsyncClient) {
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

    /**
     * Configure the base uri from using the API Schema if no baseUri is provided
     * Or from the baseUri config if provided
     *
     * @return UriInterface
     */
    private function getBaseUri()
    {
        // Create a base uri from the API Schema
        if ($this->config['baseUri'] === null) {
            $scheme = null;
            $schemes = $this->schema->getSchemes();
            if ($schemes === null) {
                throw new \LogicException('You need to provide at least on scheme in your API Schema');
            }

            foreach ($this->schema->getSchemes() as $candidate) {
                // Always prefer https
                if ($candidate === 'https') {
                    $scheme = 'https';
                }
                if ($scheme === null && $candidate === 'http') {
                    $scheme = 'http';
                }
            }
            if ($scheme === null ) {
                throw new \RuntimeException('Cannot choose a proper scheme from the API Schema. Supported: https, http');
            }

            $host = $this->schema->getHost();
            if ($host === null) {
                throw new \LogicException('The host in the API Schema should not be null');
            }

            return $this->uriFactory->createUri($scheme.'://'.$host);
        } else {
            return $this->uriFactory->createUri($this->config['baseUri']);
        }
    }

    /**
     * @param array $config
     */
    private function getConfig(array $config)
    {
        $config = array_merge(self::DEFAULT_CONFIG, $config);
        Assertion::boolean($config['returnResponse']);
        Assertion::boolean($config['validateRequest']);
        Assertion::boolean($config['validateResponse']);
        Assertion::nullOrString($config['baseUri']);

        return array_intersect_key($config, self::DEFAULT_CONFIG);
    }

    /**
     * Create an PSR-7 Request from the API Schema
     *
     * @param RequestDefinition $definition
     * @param array $params An array of parameters
     *
     * @todo handle default values for request parameters
     *
     * @return RequestInterface
     */
    private function createRequestFromDefinition(RequestDefinition $definition, array $params)
    {
        $contentType = $definition->getContentTypes()[0];
        $requestParameters = $definition->getRequestParameters();
        $path = [];
        $query = [];
        $headers = ['Content-Type' => $contentType];
        $body = null;

        foreach ($params as $name => $value) {
            $requestParameter = $requestParameters->getByName($name);
            if ($requestParameter === null) {
                throw new \InvalidArgumentException($name. ' is not a defined request parameter');
            }

            switch ($requestParameter->getLocation()) {
                case 'path':
                    $path[$name] = $value;
                    break;
                case 'query':
                    $query[$name] = $value;
                    break;
                case 'header':
                    $query[$name] = $value;
                    break;
                case 'body':
                    $body = $this->serializeRequestBody($value, $contentType);
            }
        }

        $request = $this->messageFactory->createRequest(
            $definition->getMethod(),
            $this->buildRequestUri($definition->getPathTemplate(), $path, $query),
            $headers,
            $body
        );

        return $request;
    }


    /**
     * Create a complete API Uri from the Base Uri, path and query parameters.
     *
     * Example:
     *  Given a base uri that equal http://domain.tld
     *  Given the following parameters /pets/{id}, ['id' => 1], ['foo' => 'bar']
     *  Then the Uri will equal to http://domain.tld/pets/1?foo=bar
     *
     * @param string $pathTemplate A template path
     * @param array $pathParameters Path parameters
     * @param array $queryParameters Query parameters
     *
     * @return UriInterface
     */
    private function buildRequestUri($pathTemplate, array $pathParameters, array $queryParameters)
    {
        $path = $this->uriTemplate->expand($pathTemplate, $pathParameters);
        $query = http_build_query($queryParameters);

        return $this->baseUri->withPath($path)->withQuery($query);
    }

    /**
     * @param array $decodedBody
     * @param string $contentType
     *
     * @return string
     */
    private function serializeRequestBody(array $decodedBody, $contentType)
    {
        return $this->serializer->serialize(
            $decodedBody,
            DecoderUtils::extractFormatFromContentType($contentType)
        );
    }

    /**
     * Transform a given response into a denormalized PHP object
     * If the config option "returnResponse" is set to TRUE, it return a Response instead
     *
     * @param ResponseInterface $response
     * @param ResponseDefinition $definition
     * @param RequestInterface $request
     *
     * @return Resource|mixed
     */
    private function getDataFromResponse(
        ResponseInterface $response,
        ResponseDefinition $definition,
        RequestInterface $request
    ) {
        if ($this->config['returnResponse'] === true) {
            return $response;
        }

        return $this->serializer->deserialize(
            (string) $response->getBody(),
            Resource::class,
            DecoderUtils::extractFormatFromContentType($response->getHeaderLine('Content-Type')),
            [
                'response' => $response,
                'responseDefinition' => $definition,
                'request' => $request,
            ]
        );
    }

    /**
     * Validate a Request message
     * If the config option "withRequestValidation" is set to FALSE it won't validate the Request
     *
     * @param RequestInterface $request
     * @param RequestDefinition $definition
     *
     * @throws ConstraintViolations
     */
    private function validateRequest(RequestInterface $request, RequestDefinition $definition)
    {
        if ($this->config['validateRequest'] === false) {
            return;
        }

        $this->messageValidator->validateRequest($request, $definition);
        if ($this->messageValidator->hasViolations()) {

            throw new RequestViolations(
                $this->messageValidator->getViolations()
            );
        }
    }

    /**
     * Validate a Response message
     * If the config option "withResponseValidation" is set to FALSE it won't validate the Response
     *
     * @param ResponseInterface $response
     * @param RequestDefinition $definition
     *
     * @throws ConstraintViolations
     */
    private function validateResponse(ResponseInterface $response, RequestDefinition $definition)
    {
        if ($this->config['validateResponse'] === false) {
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