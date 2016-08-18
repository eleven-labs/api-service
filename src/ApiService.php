<?php
namespace ElevenLabs\Api\Service;

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
     */
    public function __construct(
        UriInterface $baseUri,
        UriTemplate $uriTemplate,
        HttpClient $client,
        MessageFactory $messageFactory,
        Schema $schema,
        RequestValidator $validator
    ) {
        $this->baseUri = $baseUri;
        $this->uriTemplate = $uriTemplate;
        $this->schema = $schema;
        $this->validator = $validator;
        $this->client = $client;
        $this->messageFactory = $messageFactory;
    }

    /**
     * @param $name
     * @param array $params
     *
     * @return ResponseInterface
     */
    public function call($name, array $params = [], $fetch = self::FETCH_RESPONSE)
    {
        $response =  $this->client->sendRequest($this->getRequestFor($name, $params));

        return $response;
    }

    /**
     * @param string $name
     * @param array $params
     *
     * @return Promise
     */
    public function callAsync($name, array $params = [], $fetch = self::FETCH_RESPONSE)
    {
        if (! $this->client instanceof HttpAsyncClient) {
            throw new \RuntimeException(
                sprintf(
                    '"%s" does not support async request',
                    get_class($this->client)
                )
            );
        }

        $request = $this->getRequestFor($name, $params);
        $promise = $this->client->sendAsyncRequest($request);

        return $promise;
    }

    /**
     * Create an PSR-7 Request from the API Specification
     *
     * @param string $name The name of the desired operation
     * @param array $params An array of parameters
     *
     * @return \Psr\Http\Message\RequestInterface
     *
     * @throws ConstraintViolations
     */
    private function getRequestFor($name, array $params)
    {
        $query = [];
        $headers = [];
        $uriParams = [];
        $body = null;

        $definition = $this->schema->findDefinitionByOperationId($name);

        // @todo Find a may to guess the desired media type :\
        $headers['Content-Type'] = 'application/json';

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