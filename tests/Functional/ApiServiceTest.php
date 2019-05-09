<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Tests\Functional;

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

/**
 * Class ApiServiceTest.
 *
 * @group functional
 */
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
                        'url'    => 'https://httpbin.org/get',
                    ]
                )
            )
        );

        $response = $apiService->call('dumpGetRequest');

        $this->assertInstanceOf(Item::class, $response);
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
                        'url'    => 'https://httpbin.org/get',
                    ]
                )
            )
        );

        $promise = $apiService->callAsync('dumpGetRequest');

        $this->assertInstanceOf(Promise::class, $promise);
        $this->assertInstanceOf(Item::class, $promise->wait());
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
            'aBody' => ['foo' => 'bar'],
        ]);

        $request = current($this->httpMockClient->getRequests());

        $this->assertEquals('https://domain.tld/get/1?aSlug=test&aDate=notADateString', $request->getUri()->__toString());
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

    /** @test */
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
                    ],
                ],
                '[{"foo": "value 1"}, {"foo": "value 2"}]'
            )
        );

        $resource = $apiService->call('getFakeCollection');

        $this->assertInstanceOf(Collection::class, $resource);
        $this->assertTrue($resource->hasPagination());
        $this->assertInstanceOf(Pagination::class, $resource->getPagination());
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

        $this->assertInstanceOf(Item::class, $result);
        $this->assertEmpty($result->getData());
        $this->assertEmpty($result->getBody());
        $this->assertArrayHasKey('Host', $result->getMeta());
    }
}
