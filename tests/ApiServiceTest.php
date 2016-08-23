<?php
namespace ElevenLabs\Api\Service;

use ElevenLabs\Api\Decoder\Adapter\SymfonyDecoderAdapter;
use ElevenLabs\Api\Factory\SwaggerSchemaFactory;
use ElevenLabs\Api\Service\Denormalizer\ResourceDenormalizer;
use ElevenLabs\Api\Service\Serializer\Adapter\SymfonySerializerAdapter;
use ElevenLabs\Api\Service\UriTemplate\Rize\RizeUriTemplateAdapter;
use ElevenLabs\Api\Validator\RequestValidator;
use GuzzleHttp\Psr7\Uri;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use Rize\UriTemplate;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

class ApiServiceTest extends TestCase
{
    /** @test */
    public function itWork()
    {
        $schemaFactory = new SwaggerSchemaFactory();
        $jsonEncoder = new JsonEncoder();
        $schema = $schemaFactory->createSchema('file://'.__DIR__.'/fixtures/foo.yml');

        $apiService = new ApiService(
            new Uri('https://httpbin.org'),
            new RizeUriTemplateAdapter(new UriTemplate()),
            HttpClientDiscovery::find(),
            MessageFactoryDiscovery::find(),
            $schema,
            new RequestValidator($schema, new Validator(), new SymfonyDecoderAdapter($jsonEncoder)),
            new SymfonySerializerAdapter(new Serializer([new ResourceDenormalizer()],[$jsonEncoder]))
        );

        $rep = $apiService->call(
            'getSomething',
            [
                'bar' => 'cat',
                'body' => [
                    'id' => 1,
                    'name' => 'chips'
                ]
            ]);

        var_dump($rep);
    }
}