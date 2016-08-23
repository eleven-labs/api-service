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
use ElevenLabs\Api\Service\UriTemplate\UriTemplate;
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
     * @param $operationId
     * @param array $params
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
            $requestDefinition->getResponseDefinition($response->getStatusCode())
        );

        return $data;
    }

    /**
     * @param string $operationId
     * @param array $params
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

        $definition = $this->schema->getRequestDefinition($operationId);
        $request = $this->createRequestFromDefinition($definition, $params);
        $promise = $this->client->sendAsyncRequest($request);

        return $promise;
    }

    /**
     * Create an PSR-7 Request from the API Specification
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
                    $body = $this->serializeBody($value, $contentType);
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
    private function serializeBody(array $decodedBody, $contentType)
    {
        return $this->serializer->serialize(
            $decodedBody,
            $this->extractFormatFromContentType($contentType)
        );
    }

    /**
     * Transform a given response into a denormalized object
     *
     * @todo Support other type the Resource::class is forced by now
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