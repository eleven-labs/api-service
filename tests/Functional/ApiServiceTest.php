<?php
namespace ElevenLabs\Api\Service\Functional;

use ElevenLabs\Api\Service\ApiServiceBuilder;
use ElevenLabs\Api\Service\Exception\RequestViolations;
use ElevenLabs\Api\Service\Exception\ResponseViolations;
use ElevenLabs\Api\Service\Pagination\Pagination;
use ElevenLabs\Api\Service\Pagination\Provider\PaginationHeader;
use ElevenLabs\Api\Service\Resource\Collection;
use ElevenLabs\Api\Service\Resource\Item;
use GuzzleHttp\Psr7\Response;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Mock\Client;
use Http\Promise\Promise;
use PHPUnit\Framework\TestCase;

class ApiServiceTest extends TestCase
{
    /** @var string */
    private $schemaFile;
    /** @var Client */
    private $httpMockClient;

    public function setUp()
    {
        $this->schemaFile = 'file://'.__DIR__.'/../fixtures/httpbin.yml';
        $this->httpMockClient = new MockClient();
    }

    /** @test */
    public function itCanMakeASynchronousCall()
    {
        $apiService = (new ApiServiceBuilder())
            ->withHttpClient($this->httpMockClient)
            ->withMessageFactory($messageFactory = MessageFactoryDiscovery::find())
            ->build($this->schemaFile);

        $this->httpMockClient->addResponse(
            $messageFactory->createResponse(
                $statusCode   = 200,
                $reasonPhrase = '',
                $headers      = ['Content-Type' => 'application/json'],
                $body         = json_encode(
                    [
                        'origin' => '127.0.0.1',
                        'url'    => 'https://httpbin.org/get'
                    ]
                )
            )
        );

        $response = $apiService->call('dumpGetRequest');

        assertThat($response, isInstanceOf(Item::class));
    }

    /** @test */
    public function itCanMakeAnAsynchronousCall()
    {
        $apiService = (new ApiServiceBuilder())
            ->withHttpClient($this->httpMockClient)
            ->withMessageFactory($messageFactory = MessageFactoryDiscovery::find())
            ->build($this->schemaFile);

        $this->httpMockClient->addResponse(
            $messageFactory->createResponse(
                $statusCode   = 200,
                $reasonPhrase = '',
                $headers      = ['Content-Type' => 'application/json'],
                $body         = json_encode(
                    [
                        'origin' => '127.0.0.1',
                        'url'    => 'https://httpbin.org/get'
                    ]
                )
            )
        );

        $promise = $apiService->callAsync('dumpGetRequest');

        assertThat($promise, isInstanceOf(Promise::class));
        assertThat($promise->wait(), isInstanceOf(Item::class));
    }

    /** @test */
    public function itValidateTheRequestByDefault()
    {
        $this->expectException(RequestViolations::class);

        $apiService = ApiServiceBuilder::create()
            ->build($this->schemaFile);

        $apiService->call('dumpGetRequest', ['aDate' => 'notADateString']);
    }

    /** @test */
    public function itAllowTheRequestValidationToBeDisable()
    {
        $apiService = ApiServiceBuilder::create()
            ->disableRequestValidation()
            ->withHttpClient($this->httpMockClient)
            ->withBaseUri('https://domain.tld')
            ->build($this->schemaFile);

        $this->httpMockClient->addResponse(new Response(200, ['Content-Type' => 'application/json'], '{}'));

        $apiService->call('dumpGetRequest', [
            'aPath' => 1,
            'aDate' => 'notADateString',
            'aBody' => ['foo' => 'bar']
        ]);

        $request = current($this->httpMockClient->getRequests());

        assertThat($request->getUri()->__toString(), equalTo('https://domain.tld/get/1?aDate=notADateString'));
    }

    /** @test */
    public function itAllowTheResponseValidationToBeEnabled()
    {
        $this->expectException(ResponseViolations::class);

        $apiService = ApiServiceBuilder::create()
            ->enableResponseValidation()
            ->withHttpClient($this->httpMockClient)
            ->withBaseUri('https://domain.tld')
            ->build($this->schemaFile);

        $this->httpMockClient->addResponse(
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                '{"notAValidProperty": "oups"}'
            )
        );

        $apiService->call('dumpGetRequest');
    }

    public function itCanPaginate()
    {
        $apiService = ApiServiceBuilder::create()
            ->withHttpClient($this->httpMockClient)
            ->withBaseUri('https://domain.tld')
            ->withPaginationProvider(new PaginationHeader())
            ->build($this->schemaFile);

        $this->httpMockClient->addResponse(
            new Response(
                200,
                [
                    'Content-Type' => 'application/json',
                    'X-Page' => '1',
                    'X-Per-Page' => '10',
                    'X-Total-Pages' => '10',
                    'X-Total-Items' => '100',
                    'Link' => [
                        '<http://domain.tld?page=1>; rel="first"',
                        '<http://domain.tld?page=10>; rel="last"',
                        '<http://domain.tld?page=4>; rel="next"',
                        '<http://domain.tld?page=2>; rel="prev"',
                    ]
                ],
                '[{"foo": "value 1"}, {"foo": "value 2"}]'
            )
        );

        $resource = $apiService->call('getFakeCollection');

        assertThat($resource, isInstanceOf(Collection::class));
        assertThat($resource->hasPagination(), isTrue());
        assertThat($resource->getPagination(), isInstanceOf(Pagination::class));
    }

    /** @test */
    public function itDoesNotTryToValidateTheResponseBodyIfNoBodySchemaIsProvided()
    {
        $apiService = ApiServiceBuilder::create()
            ->enableResponseValidation()
            ->withHttpClient($this->httpMockClient)
            ->withMessageFactory($messageFactory = MessageFactoryDiscovery::find())
            ->build($this->schemaFile);

        $this->httpMockClient->addResponse(
            $messageFactory->createResponse(201)
        );

        $result = $apiService->call('postResponseWithoutBody');

        assertThat($result, isInstanceOf(Item::class));
        assertThat($result->getData(), isEmpty());
        assertThat($result->getMeta(), arrayHasKey('Host'));
    }
}
