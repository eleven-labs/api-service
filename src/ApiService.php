<?php
namespace ElevenLabs\Api\Service;

use ElevenLabs\Api\Service\Collection\CollectionProvider;
use ElevenLabs\Api\Service\Decoder\Decoder;
use ElevenLabs\Api\Validator\Exception\ConstraintViolations;
use ElevenLabs\Api\Validator\RequestValidator;
use ElevenLabs\Api\Validator\Schema;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Message\MessageFactory;
use Http\Promise\Promise;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use ElevenLabs\Api\Service\UriTemplate\UriTemplate;

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
     * @var Decoder
     */
    private $decoder;

    /**
     * @var CollectionProvider
     */
    private $collectionProvider;

    /**
     * Return the decoded response
     */
    const FETCH_DATA = 'data';

    /**
     * Return a ResponseInterface
     */
    const FETCH_RESPONSE = 'response';

    /**
     * @param UriInterface $baseUri The BaseUri of your API
     * @param UriTemplate $uriTemplate Used to expand Uri pattern in the API definition
     * @param HttpClient $client An HTTP client
     * @param MessageFactory $messageFactory
     * @param Schema $schema
     * @param RequestValidator $validator
     * @param Decoder $decoder
     * @param CollectionProvider $collectionProvider
     */
    public function __construct(
        UriInterface $baseUri,
        UriTemplate $uriTemplate,
        HttpClient $client,
        MessageFactory $messageFactory,
        Schema $schema,
        RequestValidator $validator,
        Decoder $decoder,
        CollectionProvider $collectionProvider
    ) {
        $this->baseUri = $baseUri;
        $this->uriTemplate = $uriTemplate;
        $this->schema = $schema;
        $this->validator = $validator;
        $this->client = $client;
        $this->messageFactory = $messageFactory;
        $this->decoder = $decoder;
        $this->collectionProvider = $collectionProvider;
    }

    /**
     * @param $operationId
     * @param array $params
     *
     * @return ResponseInterface
     */
    public function call($operationId, array $params = [], $fetch = self::FETCH_RESPONSE)
    {
        $definition = $this->schema->findDefinitionByOperationId($operationId);
        $response =  $this->client->sendRequest(
            $this->getRequestFrom($definition, $params)
        );

        if ($fetch === self::FETCH_DATA) {
            return $this->getData($response, $definition);
        }

        return $response;
    }

    /**
     * @param string $operationId
     * @param array $params
     * @param string $fetch
     *
     * @return Promise
     */
    public function callAsync($operationId, array $params = [], $fetch = self::FETCH_RESPONSE)
    {
        if (! $this->client instanceof HttpAsyncClient) {
            throw new \RuntimeException(
                sprintf(
                    '"%s" does not support async request',
                    get_class($this->client)
                )
            );
        }

        $definition = $this->schema->findDefinitionByOperationId($operationId);
        $request = $this->getRequestFrom($definition, $params);
        $promise = $this->client->sendAsyncRequest($request);

        if ($fetch === self::FETCH_DATA) {
            return $promise->then(function (ResponseInterface $response) use ($definition) {
                return $this->getData($response, $definition);
            });
        }

        return $promise;
    }

    /**
     * Decode and return data from a given Request object
     *
     * @param ResponseInterface $response
     * @param \stdClass $definition
     *
     * @return \Traversable|array
     */
    private function getData(ResponseInterface $response, \stdClass $definition)
    {
        $decodedContent = $this->decoder->decode($response->getBody());
        if ($this->isCollection($definition)) {
            return $this->collectionProvider->getCollection($response, $decodedContent);
        }

        return $decodedContent;
    }

    /**
     * @param \stdClass $definition
     *
     * @return bool
     */
    private function isCollection(\stdClass $definition)
    {
        return (isset($definition->schema) && $definition->schema->type === 'array');
    }

    /**
     * Create an PSR-7 Request from the API Specification
     *
     * @param object $definition The name of the desired operation
     * @param array $params An array of parameters
     *
     * @return \Psr\Http\Message\RequestInterface
     *
     * @throws ConstraintViolations
     */
    private function getRequestFrom($definition, array $params)
    {
        $query = [];
        $headers = ['Content-Type' => 'application/json'];
        $uriParams = [];
        $body = null;

        foreach ($definition->parameters as $parameter) {
            $name = $parameter->name;
            if (array_key_exists($name, $params)) {
                switch ($parameter->in) {
                    case 'query':
                        $query[$name] = $params[$name];
                        break;
                    case 'path';
                        $uriParams[$name] = $params[$name];
                        break;
                    case 'header';
                        $headers[$name] = $params[$name];
                        break;
                    case 'body':
                        $body = json_encode($params[$name]);
                        break;
                }
            }
        }

        $path = $this->uriTemplate->expand($definition->pattern, $uriParams);
        $queryString = http_build_query($query);

        $request = $this->messageFactory->createRequest(
            $definition->method,
            $this->baseUri->withPath($path)->withQuery($queryString),
            $headers,
            $body
        );

        $this->validator->validateRequest($request);
        if ($this->validator->hasViolations()) {
            throw $this->validator->getConstraintViolationsException();
        }

        return $request;
    }
}