<?php

namespace ElevenLabs\Api\Service\Functional;

use ElevenLabs\Api\Service\ApiServiceBuilder;
use ElevenLabs\Api\Service\Exception\RequestViolations;
use ElevenLabs\Api\Service\Exception\ResponseViolations;
use ElevenLabs\Api\Service\Pagination\Pagination;
use ElevenLabs\Api\Service\Pagination\Provider\PaginationHeader;
use ElevenLabs\Api\Service\Resource\Collection;
use ElevenLabs\Api\Service\Resource\Item;
use ElevenLabs\Api\Service\Resource\Resource;
use GuzzleHttp\Psr7\Response;
use Http\Mock\Client;
use Http\Promise\Promise;
use PHPUnit\Framework\TestCase;

/**
 * Class ApiServiceTest
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
        $this->httpMockClient = new Client();
    }

    /** @test */
    public function itCanMakeASynchronousCall()
    {
        $apiService = ApiServiceBuilder::create()
            ->withHttpClient($this->httpMockClient)
            ->build($this->schemaFile);

        $this->httpMockClient->addResponse(
            new Response(200, ['Content-Type' => 'application/json'], '{}')
        );

        $data = $apiService->call('dumpGetRequest');
        $this->assertInstanceOf(Item::class, $data);
        $this->assertEquals([], $data->getData());
        $this->assertEquals(['headers' => ['Content-Type' => ['application/json']]], $data->getMeta());
    }

    ///** @test */
    /*public function itCanMakeAnAsynchronousCall()
    {
        $apiService = ApiServiceBuilder::create()
            ->withHttpClient($this->httpMockClient)
            ->build($this->schemaFile);

        $this->httpMockClient->addResponse(
            new Response(200, ['Content-Type' => 'application/json'], '{}')
        );

        $promise = $apiService->callAsync('dumpGetRequest');
        $this->assertInstanceOf(Promise::class, $promise);

        $resource = $promise->wait();
        $this->assertInstanceOf(Resource::class, $resource);
    }*/

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

}