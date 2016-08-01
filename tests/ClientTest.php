<?php
namespace ElevenLabs\Swagger\Http;

use ElevenLabs\Swagger\Http\UriTemplate\Rize\UriTemplate;
use ElevenLabs\Swagger\RequestValidator;
use ElevenLabs\Swagger\SchemaLoader;
use GuzzleHttp\Psr7\Uri;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Discovery\MessageFactoryDiscovery;
use JsonSchema\Validator;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function testSynchronousCall()
    {
        $messageFactory = MessageFactoryDiscovery::find();

        $expectedRequest = $messageFactory->createRequest(
            'POST',
            'http://domain.tld/api/pets',
            ['Content-Type' => 'application/json'],
            '{"id": 1234, "name": "Doggy"}'
        );

        $httpClient = $this->prophesize(HttpClient::class);
        $httpClient->sendRequest($expectedRequest);

        $schema = (new SchemaLoader())->load(__DIR__.'/fixtures/petstore.yml');

        $client = new Client(
            new Uri('http://domain.tld'),
            new UriTemplate(),
            $httpClient->reveal(),
            $messageFactory,
            $schema,
            new RequestValidator($schema, new Validator())
        );

        $client->call('addPet', ['pet' => ['id' => 1234, 'name' => 'Doggy']]);
    }

    public function testAsynchronousCall()
    {
        $messageFactory = MessageFactoryDiscovery::find();

        $expectedRequest = $messageFactory->createRequest(
            'POST',
            'http://domain.tld/api/pets',
            ['Content-Type' => 'application/json'],
            '{"id": 1234, "name": "Doggy"}'
        );

        $httpClient = $this->prophesize(HttpClient::class)->willImplement(HttpAsyncClient::class);
        $httpClient->sendAsyncRequest($expectedRequest);

        $schema = (new SchemaLoader())->load(__DIR__.'/fixtures/petstore.yml');

        $client = new Client(
            new Uri('http://domain.tld'),
            new UriTemplate(),
            $httpClient->reveal(),
            $messageFactory,
            $schema,
            new RequestValidator($schema, new Validator())
        );

        $client->callAsync('addPet', ['pet' => ['id' => 1234, 'name' => 'Doggy']]);
    }
}