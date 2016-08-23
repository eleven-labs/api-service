<?php
namespace ElevenLabs\Api\Service;

use ElevenLabs\Api\Definition\RequestDefinition;
use ElevenLabs\Api\Definition\ResponseDefinition;
use ElevenLabs\Api\Schema;
use ElevenLabs\Api\Service\Resource\Resource;
use ElevenLabs\Api\Validator\RequestValidator;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Message\MessageFactory;
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
    /**
     * @var Schema
     */
    private $schema;

    /**
     * @var RequestValidator
     */
    private $validator;

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
     * @var UriInterface
     */
    private $baseUri;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param UriInterface $baseUri The BaseUri of your API
     * @param UriTemplate $uriTemplate Used to expand Uri pattern in the API definition
     * @param HttpClient $client An HTTP client
     * @param MessageFactory $messageFactory
     * @param Schema $schema
     * @param RequestValidator $validator
     * @param SerializerInterface $serializer
     */
    public function __construct(
        UriInterface $baseUri,
        UriTemplate $uriTemplate,
        HttpClient $client,
        MessageFactory $messageFactory,
        Schema $schema,
        RequestValidator $validator,
        SerializerInterface $serializer
    ) {
        $this->baseUri = $baseUri;
        $this->uriTemplate = $uriTemplate;
        $this->schema = $schema;
        $this->validator = $validator;
        $this->client = $client;
        $this->messageFactory = $messageFactory;
        $this->serializer = $serializer;
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
        $response =  $this->client->sendRequest($request);

        $data = $this->getDataFromResponse(
            $response,
            $requestDefinition->getResponseDefinition(
                $response->getStatusCode()
            )
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
            function (ResponseInterface $response) use ($requestDefinition) {

                return $this->getDataFromResponse(
                    $response,
                    $requestDefinition->getResponseDefinition(
                        $response->getStatusCode()
                    )
                );
            }
        );
    }

    /**
     * Create an PSR-7 Request from the API Schema
     *
     * @param RequestDefinition $definition
     * @param array $params An array of parameters
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
            $this->extractFormatFromContentType($contentType)
        );
    }

    /**
     * Transform a given response into a denormalized PHP object
     *
     * @todo Support other type than ElevenLabs\Api\Service\Resource
     *
     * @param ResponseInterface $response
     * @param ResponseDefinition $definition
     *
     * @return object|Resource
     */
    private function getDataFromResponse(ResponseInterface $response, ResponseDefinition $definition)
    {
        return $this->serializer->deserialize(
            (string) $response->getBody(),
            Resource::class,
            $this->extractFormatFromContentType($response->getHeaderLine('Content-Type')),
            [
                'response' => $response,
                'definition' => $definition
            ]
        );
    }

    /**
     * @param string $contentType
     *
     * @return string
     */
    private function extractFormatFromContentType($contentType)
    {
        $parts = explode('/', $contentType);
        $format = array_pop($parts);
        if (false !== $pos = strpos($format, '+')) {
            $format = substr($format, $pos+1);
        }

        return $format;
    }

}