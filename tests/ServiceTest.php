<?php
namespace ElevenLabs\Swagger\Http;

use ElevenLabs\Swagger\Http\Token\MatchRequestToken;
use ElevenLabs\Swagger\Http\UriTemplate\Rize\UriTemplate;
use ElevenLabs\Swagger\RequestValidator;
use ElevenLabs\Swagger\SchemaLoader;
use GuzzleHttp\Psr7\Uri;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Discovery\MessageFactoryDiscovery;
use JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class ServiceTest extends TestCase
{
    public function testSynchronousCall()
    {
        $expectedRequest = $this->getMessageFactory()->createRequest(
            'POST',
            'http://domain.tld/api/foo/bar?baz=baz',
            [
                'Content-Type' => 'application/json',
                'x-bat' => 'bat',
            ],
            '{"id":1234,"name":"John Doe"}'
        );

        $client = $this->getSynchronousService($expectedRequest);

        $client->call(
            'addFoo',
            [
                'bar' => 'bar',
                'baz' => 'baz',
                'x-bat' => 'bat',
                'foo' => [
                    'id' => 1234,
                    'name' => 'John Doe'
                ]
            ]
        );
    }

    private function getSynchronousService(RequestInterface $expectedRequest)
    {
        $httpClient = $this->prophesize(HttpClient::class);
        $httpClient->sendRequest(new MatchRequestToken($expectedRequest))->willReturn(null);

        return $this->getService($httpClient->reveal(), $expectedRequest);
    }

    private function getAsynchronousService(RequestInterface $expectedRequest)
    {
        $httpClient = $this->prophesize(HttpClient::class)->willImplement(HttpAsyncClient::class);
        $httpClient->sendRequest(new MatchRequestToken($expectedRequest))->willReturn(null);

        return $this->getService($httpClient->reveal(), $expectedRequest);
    }

    private function getService(HttpClient $httpClient)
    {
        $swaggerSchema = (new SchemaLoader())->load(__DIR__ . '/fixtures/foo.yml');

        return new Service(
            new Uri('http://domain.tld'),
            new UriTemplate(),
            $httpClient,
            $this->getMessageFactory(),
            $swaggerSchema,
            new RequestValidator($swaggerSchema, new Validator())
        );
    }

    /**
     * @return \Http\Message\MessageFactory
     */
    private function getMessageFactory()
    {
        return MessageFactoryDiscovery::find();
    }
}