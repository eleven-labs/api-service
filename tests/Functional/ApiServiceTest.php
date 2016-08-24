<?php
namespace ElevenLabs\Api\Service\Functional;

use ElevenLabs\Api\Service\ApiServiceBuilder;
use ElevenLabs\Api\Service\Exception\RequestViolations;
use ElevenLabs\Api\Service\Exception\ResponseViolations;
use ElevenLabs\Api\Service\Resource\Resource;
use GuzzleHttp\Psr7\Response;
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

        $apiService->call('dumpGetRequest');
    }

    /** @test */
    public function itCanMakeAnAsynchronousCall()
    {
        $apiService = ApiServiceBuilder::create()
            ->withHttpClient($this->httpMockClient)
            ->build($this->schemaFile);

        $this->httpMockClient->addResponse(
            new Response(200, ['Content-Type' => 'application/json'], '{}')
        );

        $promise = $apiService->callAsync('dumpGetRequest');
        assertThat($promise, isInstanceOf(Promise::class));

        $resource = $promise->wait();
        assertThat($resource, isInstanceOf(Resource::class));
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

}