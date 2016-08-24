<?php
namespace ElevenLabs\Api\Service;

use ElevenLabs\Api\Schema;
use ElevenLabs\Api\Validator\MessageValidator;
use Http\Client\HttpClient;
use Http\Message\MessageFactory;
use Http\Message\UriFactory;
use PHPUnit\Framework\TestCase;
use Rize\UriTemplate;
use Symfony\Component\Serializer\SerializerInterface;

class ApiServiceTest extends TestCase
{
    /** @var Schema */
    private $schema;
    /** @var UriFactory */
    private $uriFactory;
    /** @var UriTemplate */
    private $uriTemplate;
    /** @var HttpClient */
    private $httpClient;
    /** @var MessageFactory */
    private $messageFactory;
    /** @var MessageValidator */
    private $messageValidator;
    /** @var SerializerInterface */
    private $serializer;
    /** @var array */
    private $config;

    public function setUp()
    {
        $this->schema = $this->prophesize(Schema::class);
        $this->uriFactory = $this->prophesize(UriFactory::class);
        $this->uriTemplate = $this->prophesize(UriTemplate::class);
        $this->httpClient = $this->prophesize(HttpClient::class);
        $this->messageFactory = $this->prophesize(MessageFactory::class);
        $this->messageValidator = $this->prophesize(MessageValidator::class);
        $this->serializer = $this->prophesize(SerializerInterface::class);
        $this->config = [];
    }

    /** @test */
    public function itShouldCheckApiSchemaSchemes()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('You need to provide at least on scheme in your API Schema');

        $this->getApiService();
    }
    /** @test */
    public function itCheckTheHostInApiSchema()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The host in the API Schema should not be null');

        $this->schema->getSchemes()->willReturn(['https']);
        $this->schema->getHost()->willReturn(null);

        $this->getApiService();
    }

    /** @test */
    public function itOnlySupportHttpAndHttps()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot choose a proper scheme from the API Schema. Supported: https, http');

        $this->schema->getSchemes()->willReturn(['ftp']);
        $this->schema->getHost()->willReturn('domain.tld');

        $this->getApiService();
    }

    /** @test */
    public function itShouldPreferHttps()
    {
        $this->schema->getSchemes()->willReturn(['http', 'https']);
        $this->schema->getHost()->willReturn('domain.tld');

        $this->uriFactory->createUri('https://domain.tld')->shouldBeCalled();

        $this->getApiService();
    }

    /** @test */
    public function itCreateABaseUriUsingTheOneProvidedInTheConfigArray()
    {
        $this->config['baseUri'] = 'https://somewhere.tld';
        $this->uriFactory->createUri('https://somewhere.tld')->shouldBeCalled();

        $this->getApiService();
    }

    public function itCanMakeASynchronousCall()
    {
        // @todo make unit test for itCanMakeASynchronousCall()
    }

    public function itCanMakeAnAsynchronousCall()
    {
        // @todo make unit test for itCanMakeAnAsynchronousCall()
    }

    private function getApiService()
    {
        return new ApiService(
            $this->uriFactory->reveal(),
            $this->uriTemplate->reveal(),
            $this->httpClient->reveal(),
            $this->messageFactory->reveal(),
            $this->schema->reveal(),
            $this->messageValidator->reveal(),
            $this->serializer->reveal(),
            $this->config
        );
    }
}