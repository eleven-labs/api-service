<?php
namespace ElevenLabs\Swagger\Http;

use ElevenLabs\Swagger\Exception\ConstraintViolations;
use ElevenLabs\Swagger\Http\UriTemplate\UriTemplate;
use ElevenLabs\Swagger\RequestValidator;
use ElevenLabs\Swagger\Schema;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Message\MessageFactory;
use Http\Promise\Promise;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use \RuntimeException;

class ServiceTest extends TestCase
{
    /** @var ObjectProphecy|UriInterface */
    private $baseUri;
    /** @var ObjectProphecy|UriTemplate */
    private $uriTemplate;
    /** @var ObjectProphecy|MessageFactory */
    private $messageFactory;
    /** @var ObjectProphecy|Schema */
    private $swaggerSchema;
    /** @var ObjectProphecy|RequestValidator */
    private $requestValidator;

    public function setUp()
    {
        $this->baseUri = $this->prophesize(UriInterface::class);
        $this->uriTemplate = $this->prophesize(UriTemplate::class);
        $this->messageFactory = $this->prophesize(MessageFactory::class);
        $this->swaggerSchema = $this->prophesize(Schema::class);
        $this->requestValidator = $this->prophesize(RequestValidator::class);
    }

    public function testItShouldTransformBodyParamIntoJson()
    {
        $expectedJson = '{"id":1234,"name":"John Doe"}';

        $definition  = [
            'method' => 'POST',
            'pattern' => '/api/foo',
            'parameters' => [
                [
                    'name' => 'a_body',
                    'in' => 'body',
                    'required' => true,
                    'schema' => [
                        'type' => 'object'
                    ]
                ]
            ]
        ];

        $this->swaggerSchema->findDefinitionByOperationId('addFoo')->willReturn(self::arrayToObject($definition));

        $this->stubBaseUri();
        $this->stubUriTemplate();
        $this->stubRequestValidator();

        $this->messageFactory->createRequest(
            Argument::any(),
            Argument::any(),
            Argument::any(),
            Argument::exact($expectedJson)
        )->willReturn($this->prophesize(RequestInterface::class));

        $this->getService()->call(
            'addFoo',
            [
                'a_body' => [
                    'id' => 1234,
                    'name' => 'John Doe'
                ]
            ]
        );
    }

    public function testItBuildTheResourceUri()
    {
        $definition  = [
            'method' => 'GET',
            'pattern' => '/api/foo/{id}',
            'parameters' => [
                [
                    'name' => 'id',
                    'in' => 'path',
                    'type' => 'integer',
                    'required' => true,
                ],
                [
                    'name' => 'bar',
                    'in' => 'query',
                    'type' => 'string',
                    'required' => true,
                ]
            ]
        ];

        $this->uriTemplate->expand('/api/foo/{id}', ['id' => 123])->willReturn('/api/foo/123');
        $this->baseUri->withPath('/api/foo/123')->willReturn($this->baseUri);
        $this->baseUri->withQuery('bar=value')->willReturn($this->baseUri);
        $this->swaggerSchema->findDefinitionByOperationId('getFoo')->willReturn(self::arrayToObject($definition));

        $this->stubRequestValidator();
        $this->stubMessageFactory();

        $this->getService()->call('getFoo', ['id' => 123, 'bar' => 'value']);
    }

    public function testItShouldHandleRequestHeaders()
    {
        $expectedHeaders = [
            'Content-Type' => 'application/json',
            'x-bar' => 'value'
        ];

        $definition  = [
            'method' => 'GET',
            'pattern' => '/api/foo',
            'parameters' => [
                [
                    'name' => 'x-bar',
                    'in' => 'header',
                    'type' => 'integer',
                    'required' => true,
                ]
            ]
        ];

        $this->swaggerSchema->findDefinitionByOperationId('addFoo')->willReturn(self::arrayToObject($definition));

        $this->stubBaseUri();
        $this->stubUriTemplate();
        $this->stubRequestValidator();

        $this->messageFactory->createRequest(
            Argument::any(),
            Argument::any(),
            Argument::exact($expectedHeaders),
            Argument::any()
        )->willReturn($this->prophesize(RequestInterface::class));

        $this->getService()->call('addFoo', ['x-bar' => 'value']);
    }

    public function testItShouldThrowAConstraintViolationsException()
    {
        $this->expectException(ConstraintViolations::class);

        $this->stubSwaggerSchema();
        $this->stubBaseUri();
        $this->stubMessageFactory();

        $this->requestValidator->validateRequest(Argument::type(RequestInterface::class))->willReturn(null);
        $this->requestValidator->hasViolations()->willReturn(true);
        $this->requestValidator->getConstraintViolationsException()->willReturn(
            $this->prophesize(ConstraintViolations::class)
        );

        $this->getService()->call('getFoo');
    }

    public function testItThrowAnExceptionWhenUsingCallAsyncWithASynchronousHttpClient()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageRegExp('/does not support async request$/');
        $this->getService()->callAsync('getFoo');
    }

    public function testItReturnAPromiseOnCallAsync()
    {
        $httpAsyncClient = $this->prophesize(HttpClient::class)->willImplement(HttpAsyncClient::class);
        $httpAsyncClient->sendAsyncRequest(Argument::type(RequestInterface::class))->willReturn(
            $this->prophesize(Promise::class)
        );

        $this->stubSwaggerSchema();
        $this->stubBaseUri();
        $this->stubMessageFactory();
        $this->stubRequestValidator();

        $promise = $this->getService($httpAsyncClient)->callAsync('getFoo');

        self::assertInstanceOf(Promise::class, $promise);
    }

    /**
     * @return Service
     */
    public function getService($httpClient = null)
    {
        if ($httpClient === null) {
            $httpClient = $this->prophesize(HttpClient::class);
        }

        return new Service(
            $this->baseUri->reveal(),
            $this->uriTemplate->reveal(),
            $httpClient->reveal(),
            $this->messageFactory->reveal(),
            $this->swaggerSchema->reveal(),
            $this->requestValidator->reveal()
        );
    }

    public function getServiceAsync()
    {
        return new Service(
            $this->baseUri->reveal(),
            $this->uriTemplate->reveal(),
            $this->httpClient->willImplement(HttpAsyncClient::class)->reveal(),
            $this->messageFactory->reveal(),
            $this->swaggerSchema->reveal(),
            $this->requestValidator->reveal()
        );
    }

    private function stubSwaggerSchema()
    {
        $emptyDefinition = [
            'method' => 'GET',
            'pattern' => '/api/foo',
            'parameters' => []
        ];

        $this->swaggerSchema->findDefinitionByOperationId(Argument::any())->willReturn(self::arrayToObject($emptyDefinition));
    }

    private function stubBaseUri()
    {
        $this->baseUri->withPath(Argument::any())->willReturn($this->baseUri);
        $this->baseUri->withQuery(Argument::any())->willReturn($this->baseUri);
    }

    private function stubUriTemplate()
    {
        $this->uriTemplate->expand(Argument::cetera())->willReturn(null);
    }

    private function stubRequestValidator()
    {
        $this->requestValidator->validateRequest(Argument::any())->willReturn(null);
        $this->requestValidator->hasViolations()->willReturn(false);
    }

    private function stubMessageFactory()
    {
        $this->messageFactory->createRequest(Argument::cetera())->willReturn(
            $this->prophesize(RequestInterface::class)
        );
    }

    private static function arrayToObject(array $array)
    {
        return json_decode(json_encode($array));
    }
}