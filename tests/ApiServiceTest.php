<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Tests;

use ElevenLabs\Api\Definition\Parameter;
use ElevenLabs\Api\Definition\Parameters;
use ElevenLabs\Api\Definition\RequestDefinition;
use ElevenLabs\Api\Definition\ResponseDefinition;
use ElevenLabs\Api\Schema;
use ElevenLabs\Api\Service\ApiService;
use ElevenLabs\Api\Service\Exception\RequestViolations;
use ElevenLabs\Api\Service\Exception\ResponseViolations;
use ElevenLabs\Api\Validator\ConstraintViolation;
use ElevenLabs\Api\Validator\MessageValidator;
use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;
use Http\Message\MessageFactory;
use Http\Message\UriFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Rize\UriTemplate;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class ApiServiceTest.
 */
class ApiServiceTest extends TestCase
{
    /** @var Schema|MockObject */
    private $schema;

    /** @var Schema|MockObject */
    private $uri;

    /** @var UriFactory|MockObject */
    private $uriFactory;

    /** @var UriTemplate|MockObject */
    private $uriTemplate;

    /** @var HttpClient|MockObject */
    private $httpClient;

    /** @var MessageFactory|MockObject */
    private $messageFactory;

    /** @var MessageValidator|MockObject */
    private $messageValidator;

    /** @var SerializerInterface|MockObject */
    private $serializer;

    /** @var array */
    private $config;

    public function setUp()
    {
        $this->uri = $this->createMock(UriInterface::class);
        $this->schema = $this->createMock(Schema::class);
        $this->uriFactory = $this->createMock(UriFactory::class);
        $this->uriTemplate = $this->createMock(UriTemplate::class);
        $this->httpClient = $this->createMock(HttpClient::class);
        $this->messageFactory = $this->createMock(MessageFactory::class);
        $this->messageValidator = $this->createMock(MessageValidator::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
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

        $this->schema->expects($this->exactly(2))->method('getSchemes')->willReturn(['https']);
        $this->schema->expects($this->once())->method('getHost')->willReturn(null);

        $this->getApiService();
    }

    /** @test */
    public function itOnlySupportHttpAndHttps()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot choose a proper scheme from the API Schema. Supported: https, http');

        $this->schema->expects($this->any())->method('getSchemes')->willReturn(['ftp']);
        $this->schema->expects($this->any())->method('getHost')->willReturn('domain.tld');

        $this->getApiService();
    }

    /** @test */
    public function itShouldPreferHttps()
    {
        $this->schema->expects($this->exactly(2))->method('getSchemes')->willReturn(['http', 'https']);
        $this->schema->expects($this->exactly(1))->method('getHost')->willReturn('domain.tld');

        $this->uriFactory->expects($this->exactly(1))->method('createUri')->with('https://domain.tld')->willReturn($this->uri);

        $this->getApiService();
    }

    /** @test */
    public function itShouldPreferHttpsByDefault()
    {
        $this->schema->expects($this->exactly(2))->method('getSchemes')->willReturn(['http']);
        $this->schema->expects($this->exactly(1))->method('getHost')->willReturn('domain.tld');

        $this->uriFactory->expects($this->exactly(1))->method('createUri')->with('http://domain.tld')->willReturn($this->uri);

        $this->getApiService();
    }

    /** @test */
    public function itCreateABaseUriUsingTheOneProvidedInTheConfigArray()
    {
        $this->config['baseUri'] = 'https://somewhere.tld';

        $this->uriFactory->expects($this->exactly(1))->method('createUri')->with('https://somewhere.tld')->willReturn($this->uri);

        $this->getApiService();
    }

    /**
     * @test
     *
     * @dataProvider dataProviderItShouldUseDefaultValues
     *
     * @param array $requestParams
     * @param array $expected
     *
     * @throws \Assert\AssertionFailedException
     * @throws \ElevenLabs\Api\Service\Exception\ConstraintViolations
     * @throws \Http\Client\Exception
     */
    public function itShouldUseDefaultValues(array $requestParams, array $expected)
    {
        $requestParameters = [];
        foreach ($requestParams as $p) {
            $requestParameter = $this->getMockBuilder(Parameter::class)->disableOriginalConstructor()->getMock();
            $requestParameter->expects($this->once())->method('getLocation')->willReturn($p['location']);
            $requestParameter->expects($this->exactly(2))->method('getSchema')->willReturnCallback(function () use ($p) {
                $value = new \stdClass();
                $value->default = $p['default'];

                return $value;
            });
            if ("body" === $p['location']) {
                $this->serializer->expects($this->once())->method('serialize')->willReturn(json_encode($p['default']));
            } else {
                $this->serializer->expects($this->never())->method('serialize');
            }
            $requestParameters[$p['name']] = $requestParameter;
        }
        $this->uriTemplate->expects($this->any())->method('expand')->willReturnCallback(function ($pathTemplate, array $pathParameters) use ($expected) {
            $this->assertEquals('/foo/bar', $pathTemplate);
            $this->assertEquals($expected['path'], $pathParameters);
        });

        $params = $this->getMockBuilder(Parameters::class)->disableOriginalConstructor()->getMock();
        $params->expects($this->once())->method('getIterator')->willReturn($requestParameters);
        $params->expects($this->never())->method('getByName');

        $baseUri = $this->createMock(UriInterface::class);
        $baseUri->expects($this->once())->method('withPath')->willReturn($baseUri);
        $baseUri->expects($this->once())->method('withQuery')->willReturnCallback(function ($query) use ($baseUri, $expected) {
            $this->assertEquals($expected['query'], $query);

            return $baseUri;
        });

        $responseDefinition = $this->getMockBuilder(ResponseDefinition::class)->disableOriginalConstructor()->getMock();
        $responseDefinition->expects($this->once())->method('hasBodySchema')->willReturn(false);

        $requestDefinition = $this->getMockBuilder(RequestDefinition::class)->disableOriginalConstructor()->getMock();
        $requestDefinition->expects($this->once())->method('getContentTypes')->willReturn(['application/json']);
        $requestDefinition->expects($this->once())->method('getRequestParameters')->willReturn($params);
        $requestDefinition->expects($this->once())->method('getMethod')->willReturn('GET');
        $requestDefinition->expects($this->once())->method('getPathTemplate')->willReturn('/foo/bar');
        $requestDefinition->expects($this->once())->method('getResponseDefinition')->with(200)->willReturn($responseDefinition);

        $this->config['baseUri'] = 'https://somewhere.tld';
        $this->uriFactory->expects($this->exactly(1))->method('createUri')->with('https://somewhere.tld')->willReturn($baseUri);
        $this->schema->expects($this->exactly(1))->method('getRequestDefinition')->with('operationId')->willReturn($requestDefinition);
        $this->messageFactory->expects($this->once())->method('createRequest')->willReturnCallback(function ($method, $uri, $headers, $body) use ($expected) {
            $this->assertEquals('GET', $method);
            $this->assertInstanceOf(UriInterface::class, $uri);
            $this->assertEquals($expected['headers'], $headers);
            $this->assertEquals($expected['body'], $body);

            $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
            $request->expects($this->once())->method('getHeaders')->willReturn([]);

            return $request;
        });
        $this->httpClient->expects($this->once())->method('sendRequest')->willReturnCallback(function ($request) {
            $this->assertInstanceOf(Request::class, $request);

            $response = $this->createMock(ResponseInterface::class);
            $response->expects($this->once())->method('getStatusCode')->willReturn(200);

            return $response;
        });

        $service = $this->getApiService();
        $service->call('operationId');
    }

    /**
     * @return array
     */
    public function dataProviderItShouldUseDefaultValues()
    {
        return [
            "path" => [
                [
                    [
                        'name' => 'foo',
                        'location' => 'path',
                        'value' => 'bar',
                        "default" => 'bar_default',
                    ],
                ],
                [
                    "path" => ['foo' => 'bar_default'],
                    "query" => null,
                    "headers" => ["Content-Type" => "application/json"],
                    'body' => null,
                ],
            ],
            "query" => [
                [
                    [
                        'name' => 'foo',
                        'location' => 'query',
                        'value' => 'bar',
                        "default" => 'bar_default',
                    ],
                ],
                [
                    "path" => [],
                    "query" => "foo=bar_default",
                    "headers" => ["Content-Type" => "application/json"],
                    'body' => null,
                ],
            ],
            "header" => [
                [
                    [
                        'name' => 'foo',
                        'location' => 'header',
                        'value' => 'bar',
                        "default" => 'bar_default',
                    ],
                ],
                [
                    "path" => [],
                    "query" => null,
                    "headers" => ["Content-Type" => "application/json", "foo" => "bar_default"],
                    'body' => null,
                ],
            ],
            "formData" => [
                [
                    [
                        'name' => 'foo',
                        'location' => 'formData',
                        'value' => 'bar',
                        "default" => 'bar_default',
                    ],
                    [
                        'name' => 'foo2',
                        'location' => 'formData',
                        'value' => 'bar2',
                        "default" => 'bar2_default',
                    ],
                ],
                [
                    "path" => [],
                    "query" => null,
                    "headers" => ["Content-Type" => "application/json"],
                    'body' => "foo=bar_default&foo2=bar2_default",
                ],
            ],
            "body" => [
                [
                    [
                        'name' => 'body',
                        'location' => 'body',
                        'value' => ['foo' => "bar", 'bar' => 'foo'],
                        "default" => ['foo' => "bar__default", 'bar' => 'foo__default'],
                    ],
                ],
                [
                    "path" => [],
                    "query" => null,
                    "headers" => ["Content-Type" => "application/json"],
                    'body' => json_encode(['foo' => "bar__default", 'bar' => 'foo__default']),
                ],
            ],
        ];
    }

    /**
     * @test
     *
     * @expectedException \InvalidArgumentException
     *
     * @expectedExceptionMessage foo is not a defined request parameter
     */
    public function itShouldNotCreateRequestBecauseSomeParameterWasNotDefined()
    {
        $this->uriTemplate->expects($this->never())->method('expand');

        $params = $this->getMockBuilder(Parameters::class)->disableOriginalConstructor()->getMock();
        $params->expects($this->once())->method('getIterator')->willReturn([]);
        $params->expects($this->once())->method('getByName')->willReturn(null);

        $baseUri = $this->createMock(UriInterface::class);
        $baseUri->expects($this->never())->method('withPath');
        $baseUri->expects($this->never())->method('withQuery');

        $requestDefinition = $this->getMockBuilder(RequestDefinition::class)->disableOriginalConstructor()->getMock();
        $requestDefinition->expects($this->once())->method('getContentTypes')->willReturn(['application/json']);
        $requestDefinition->expects($this->once())->method('getRequestParameters')->willReturn($params);
        $requestDefinition->expects($this->never())->method('getMethod');
        $requestDefinition->expects($this->never())->method('getPathTemplate');
        $requestDefinition->expects($this->never())->method('getResponseDefinition');

        $this->config['baseUri'] = 'https://somewhere.tld';
        $this->uriFactory->expects($this->exactly(1))->method('createUri')->with('https://somewhere.tld')->willReturn($baseUri);
        $this->schema->expects($this->exactly(1))->method('getRequestDefinition')->with('operationId')->willReturn($requestDefinition);
        $this->messageFactory->expects($this->never())->method('createRequest');
        $this->httpClient->expects($this->never())->method('sendRequest');
        $this->messageValidator->expects($this->never())->method('validateResponse');
        $this->messageValidator->expects($this->never())->method('hasViolations');

        $service = $this->getApiService();
        $service->call('operationId', ['foo' => 'bar']);
    }

    /**
     * @test
     *
     * @dataProvider dataProviderItShouldCreateRequestFromDefinition
     *
     * @param array $localisations
     * @param array $requestParams
     * @param array $expected
     *
     * @throws \Assert\AssertionFailedException
     */
    public function itShouldCreateRequestFromDefinition(array $localisations, array $requestParams, array $expected)
    {
        $this->uriTemplate->expects($this->any())->method('expand')->willReturnCallback(function ($pathTemplate, array $pathParameters) use ($expected) {
            $this->assertEquals('/foo/bar', $pathTemplate);
            $this->assertEquals($expected['path'], $pathParameters);
        });

        $params = $this->getMockBuilder(Parameters::class)->disableOriginalConstructor()->getMock();
        $params->expects($this->once())->method('getIterator')->willReturn([]);
        $params->expects($this->any())->method('getByName')->willReturnCallback(function ($name) use ($localisations, $requestParams) {
            $param = $this->getMockBuilder(Parameter::class)->disableOriginalConstructor()->getMock();
            $param->expects($this->once())->method('getLocation')->willReturn($localisations[$name]);
            if ("body" === $localisations[$name]) {
                $this->serializer->expects($this->once())->method('serialize')->willReturn(json_encode($requestParams[$name]));
            } else {
                $this->serializer->expects($this->never())->method('serialize');
            }

            return $param;
        });

        $baseUri = $this->createMock(UriInterface::class);
        $baseUri->expects($this->once())->method('withPath')->willReturn($baseUri);
        $baseUri->expects($this->once())->method('withQuery')->willReturnCallback(function ($query) use ($baseUri, $expected) {
            $this->assertEquals($expected['query'], $query);

            return $baseUri;
        });

        $responseDefinition = $this->getMockBuilder(ResponseDefinition::class)->disableOriginalConstructor()->getMock();
        $responseDefinition->expects($this->once())->method('hasBodySchema')->willReturn(false);

        $requestDefinition = $this->getMockBuilder(RequestDefinition::class)->disableOriginalConstructor()->getMock();
        $requestDefinition->expects($this->once())->method('getContentTypes')->willReturn(['application/json']);
        $requestDefinition->expects($this->once())->method('getRequestParameters')->willReturn($params);
        $requestDefinition->expects($this->once())->method('getMethod')->willReturn('GET');
        $requestDefinition->expects($this->once())->method('getPathTemplate')->willReturn('/foo/bar');
        $requestDefinition->expects($this->once())->method('getResponseDefinition')->with(200)->willReturn($responseDefinition);

        $this->config['baseUri'] = 'https://somewhere.tld';
        $this->config['validateResponse'] = true;
        $this->uriFactory->expects($this->exactly(1))->method('createUri')->with('https://somewhere.tld')->willReturn($baseUri);
        $this->schema->expects($this->exactly(1))->method('getRequestDefinition')->with('operationId')->willReturn($requestDefinition);
        $this->messageFactory->expects($this->once())->method('createRequest')->willReturnCallback(function ($method, $uri, $headers, $body) use ($expected) {
            $this->assertEquals('GET', $method);
            $this->assertInstanceOf(UriInterface::class, $uri);
            $this->assertEquals($expected['headers'], $headers);
            $this->assertEquals($expected['body'], $body);

            $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
            $request->expects($this->once())->method('getHeaders')->willReturn([]);

            return $request;
        });
        $this->httpClient->expects($this->once())->method('sendRequest')->willReturnCallback(function ($request) {
            $this->assertInstanceOf(Request::class, $request);

            $response = $this->createMock(ResponseInterface::class);
            $response->expects($this->once())->method('getStatusCode')->willReturn(200);

            return $response;
        });
        $this->messageValidator->expects($this->once())->method('validateResponse');
        $this->messageValidator->expects($this->exactly(2))->method('hasViolations')->willReturn(false);

        $service = $this->getApiService();
        $service->call('operationId', $requestParams);
    }

    /**
     * @return array
     */
    public function dataProviderItShouldCreateRequestFromDefinition()
    {
        return [
            "path" => [
                [
                    'foo' => 'path',
                ],
                [
                    'foo' => 'bar',
                ],
                [
                    "path" => ['foo' => 'bar'],
                    "query" => null,
                    "headers" => ["Content-Type" => "application/json"],
                    'body' => null,
                ],
            ],
            "query" => [
                [
                    'foo' => 'query',
                ],
                [
                    'foo' => 'bar',
                ],
                [
                    "path" => [],
                    "query" => "foo=bar",
                    "headers" => ["Content-Type" => "application/json"],
                    'body' => null,
                ],
            ],
            "header" => [
                [
                    'foo' => 'header',
                ],
                [
                    'foo' => 'bar',
                ],
                [
                    "path" => [],
                    "query" => null,
                    "headers" => ["Content-Type" => "application/json", "foo" => "bar"],
                    'body' => null,
                ],
            ],
            "formData" => [
                [
                    'foo' => 'formData',
                    "foo2" => 'formData',
                ],
                [
                    'foo' => 'bar',
                    "foo2" => 'bar2',
                ],
                [
                    "path" => [],
                    "query" => null,
                    "headers" => ["Content-Type" => "application/json"],
                    'body' => "foo=bar&foo2=bar2",
                ],
            ],
            "body" => [
                [
                    'body' => 'body',
                ],
                [
                    'body' => ['foo' => "bar", 'bar' => 'foo'],
                ],
                [
                    "path" => [],
                    "query" => null,
                    "headers" => ["Content-Type" => "application/json"],
                    'body' => json_encode(['foo' => "bar", 'bar' => 'foo']),
                ],
            ],
        ];
    }

    /**
     * @test
     *
     * @throws \Assert\AssertionFailedException
     * @throws \ElevenLabs\Api\Service\Exception\ConstraintViolations
     * @throws \Http\Client\Exception
     */
    public function itCanMakeASynchronousCallWithoutDefaultValueAndParameters()
    {
        $params = $this->getMockBuilder(Parameters::class)->disableOriginalConstructor()->getMock();
        $params->expects($this->once())->method('getIterator')->willReturn([]);
        $params->expects($this->never())->method('getByName');

        $baseUri = $this->createMock(UriInterface::class);
        $baseUri->expects($this->once())->method('withPath')->willReturn($baseUri);
        $baseUri->expects($this->once())->method('withQuery')->willReturn($baseUri);

        $responseDefinition = $this->getMockBuilder(ResponseDefinition::class)->disableOriginalConstructor()->getMock();
        $responseDefinition->expects($this->once())->method('hasBodySchema')->willReturn(true);

        $requestDefinition = $this->getMockBuilder(RequestDefinition::class)->disableOriginalConstructor()->getMock();
        $requestDefinition->expects($this->once())->method('getContentTypes')->willReturn(['application/json']);
        $requestDefinition->expects($this->once())->method('getRequestParameters')->willReturn($params);
        $requestDefinition->expects($this->once())->method('getMethod')->willReturn('GET');
        $requestDefinition->expects($this->once())->method('getPathTemplate')->willReturn('/foo/bar');
        $requestDefinition->expects($this->once())->method('getResponseDefinition')->with(200)->willReturn($responseDefinition);

        $this->config['baseUri'] = 'https://somewhere.tld';
        $this->uriFactory->expects($this->exactly(1))->method('createUri')->with('https://somewhere.tld')->willReturn($baseUri);
        $this->schema->expects($this->exactly(1))->method('getRequestDefinition')->with('operationId')->willReturn($requestDefinition);
        $this->messageFactory->expects($this->once())->method('createRequest')->willReturnCallback(function ($method, $uri, $headers, $body) {
            $this->assertEquals('GET', $method);
            $this->assertInstanceOf(UriInterface::class, $uri);
            $this->assertEquals(['Content-Type' => "application/json"], $headers);
            $this->assertNull($body);

            $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

            return $request;
        });
        $this->httpClient->expects($this->once())->method('sendRequest')->willReturnCallback(function ($request) {
            $this->assertInstanceOf(Request::class, $request);

            $stream =  $this->createMock(StreamInterface::class);
            $stream->expects($this->once())->method('__toString')->willReturn('body');

            $response = $this->createMock(ResponseInterface::class);
            $response->expects($this->once())->method('getStatusCode')->willReturn(200);
            $response->expects($this->once())->method('getBody')->willReturn($stream);
            $response->expects($this->once())->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');

            return $response;
        });
        $this->serializer->expects($this->once())->method('deserialize')->willReturn(["items" => ['foo', 'bar']]);
        $this->messageValidator->expects($this->never())->method('validateResponse');
        $this->messageValidator->expects($this->exactly(1))->method('hasViolations')->willReturn(false);

        $service = $this->getApiService();
        $data = $service->call('operationId');
        $this->assertSame(['items' => ['foo', 'bar']], $data);
    }

    /**
     * @test
     *
     * @dataProvider dataProviderItShouldThrowExceptionBecauseThereAreSomeError
     *
     * @param array $config
     *
     * @expectedException \Assert\AssertionFailedException
     *
     * @throws \ElevenLabs\Api\Service\Exception\ConstraintViolations
     * @throws \Http\Client\Exception
     */
    public function itShouldThrowExceptionBecauseThereAreSomeError(array $config)
    {
        $this->uriTemplate->expects($this->never())->method('expand');

        $params = $this->getMockBuilder(Parameters::class)->disableOriginalConstructor()->getMock();
        $params->expects($this->never())->method('getIterator');
        $params->expects($this->never())->method('getByName');

        $responseDefinition = $this->getMockBuilder(ResponseDefinition::class)->disableOriginalConstructor()->getMock();
        $responseDefinition->expects($this->never())->method('hasBodySchema');

        $this->config['baseUri'] = 'https://somewhere.tld';
        $this->config = array_merge($this->config, $config);
        $this->uriFactory->expects($this->never())->method('createUri')->with('https://somewhere.tld');
        $this->schema->expects($this->never())->method('getRequestDefinition')->with('operationId');
        $this->messageFactory->expects($this->never())->method('createRequest');
        $this->httpClient->expects($this->never())->method('sendRequest');

        $service = $this->getApiService();
        $response = $service->call('operationId', ['foo' => 'bar']);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @return array
     */
    public function dataProviderItShouldThrowExceptionBecauseThereAreSomeError(): array
    {
        return [
            [['returnResponse' => 'foo']],
            [['validateRequest' => 'foo']],
            [['validateResponse' => 'foo']],
            [['baseUri' => 42]],
        ];
    }

    /** @test */
    public function itShouldReturnTheResponse()
    {
        $this->uriTemplate->expects($this->once())->method('expand')->willReturnCallback(function ($pathTemplate, array $pathParameters) {
            $this->assertEquals('/foo/bar', $pathTemplate);
            $this->assertEquals([], $pathParameters);
        });

        $params = $this->getMockBuilder(Parameters::class)->disableOriginalConstructor()->getMock();
        $params->expects($this->once())->method('getIterator')->willReturn([]);
        $params->expects($this->once())->method('getByName')->willReturnCallback(function ($name) {
            $this->assertEquals("foo", $name);
            $param = $this->getMockBuilder(Parameter::class)->disableOriginalConstructor()->getMock();
            $param->expects($this->once())->method('getLocation')->willReturn('query');

            return $param;
        });

        $baseUri = $this->createMock(UriInterface::class);
        $baseUri->expects($this->once())->method('withPath')->willReturn($baseUri);
        $baseUri->expects($this->once())->method('withQuery')->willReturnCallback(function ($query) use ($baseUri) {
            $this->assertEquals('foo=bar', $query);

            return $baseUri;
        });

        $responseDefinition = $this->getMockBuilder(ResponseDefinition::class)->disableOriginalConstructor()->getMock();
        $responseDefinition->expects($this->never())->method('hasBodySchema');

        $requestDefinition = $this->getMockBuilder(RequestDefinition::class)->disableOriginalConstructor()->getMock();
        $requestDefinition->expects($this->once())->method('getContentTypes')->willReturn(['application/json']);
        $requestDefinition->expects($this->once())->method('getRequestParameters')->willReturn($params);
        $requestDefinition->expects($this->once())->method('getMethod')->willReturn('GET');
        $requestDefinition->expects($this->once())->method('getPathTemplate')->willReturn('/foo/bar');
        $requestDefinition->expects($this->once())->method('getResponseDefinition')->with(200)->willReturn($responseDefinition);

        $this->config['baseUri'] = 'https://somewhere.tld';
        $this->config['returnResponse'] = true;
        $this->uriFactory->expects($this->exactly(1))->method('createUri')->with('https://somewhere.tld')->willReturn($baseUri);
        $this->schema->expects($this->exactly(1))->method('getRequestDefinition')->with('operationId')->willReturn($requestDefinition);
        $this->messageFactory->expects($this->once())->method('createRequest')->willReturnCallback(function ($method, $uri, $headers, $body) {
            $this->assertEquals('GET', $method);
            $this->assertInstanceOf(UriInterface::class, $uri);
            $this->assertEquals(['Content-Type' => 'application/json'], $headers);
            $this->assertNull($body);

            $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

            return $request;
        });
        $this->httpClient->expects($this->once())->method('sendRequest')->willReturnCallback(function ($request) {
            $this->assertInstanceOf(Request::class, $request);

            $response = $this->createMock(ResponseInterface::class);
            $response->expects($this->once())->method('getStatusCode')->willReturn(200);
            $response->expects($this->never())->method('getBody');

            return $response;
        });

        $service = $this->getApiService();
        $response = $service->call('operationId', ['foo' => 'bar']);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /** @test */
    public function itShouldNotReturnTheResponseOrAnyDataBecauseThereAreSomeViolationInRequest()
    {
        $this->uriTemplate->expects($this->once())->method('expand')->willReturnCallback(function ($pathTemplate, array $pathParameters) {
            $this->assertEquals('/foo/bar', $pathTemplate);
            $this->assertEquals([], $pathParameters);
        });

        $params = $this->getMockBuilder(Parameters::class)->disableOriginalConstructor()->getMock();
        $params->expects($this->once())->method('getIterator')->willReturn([]);
        $params->expects($this->once())->method('getByName')->willReturnCallback(function ($name) {
            $this->assertEquals('foo', $name);
            $param = $this->getMockBuilder(Parameter::class)->disableOriginalConstructor()->getMock();
            $param->expects($this->once())->method('getLocation')->willReturn('query');

            return $param;
        });

        $baseUri = $this->createMock(UriInterface::class);
        $baseUri->expects($this->once())->method('withPath')->willReturnCallback(function ($path) use ($baseUri) {
            $this->assertEquals("", $path);

            return $baseUri;
        });
        $baseUri->expects($this->once())->method('withQuery')->willReturnCallback(function ($query) use ($baseUri) {
            $this->assertEquals("foo=bar", $query);

            return $baseUri;
        });

        $requestDefinition = $this->getMockBuilder(RequestDefinition::class)->disableOriginalConstructor()->getMock();
        $requestDefinition->expects($this->once())->method('getContentTypes')->willReturn(['application/json']);
        $requestDefinition->expects($this->once())->method('getRequestParameters')->willReturn($params);
        $requestDefinition->expects($this->once())->method('getMethod')->willReturn('GET');
        $requestDefinition->expects($this->once())->method('getPathTemplate')->willReturn('/foo/bar');
        $requestDefinition->expects($this->never())->method('getResponseDefinition');

        $this->config['baseUri'] = 'https://somewhere.tld';
        $this->config['validateResponse'] = true;
        $this->config['validateRequest'] = true;
        $this->uriFactory->expects($this->exactly(1))->method('createUri')->with('https://somewhere.tld')->willReturn($baseUri);
        $this->schema->expects($this->exactly(1))->method('getRequestDefinition')->with('operationId')->willReturn($requestDefinition);
        $this->messageFactory->expects($this->once())->method('createRequest')->willReturnCallback(function ($method, $uri, $headers, $body) {
            $this->assertEquals('GET', $method);
            $this->assertInstanceOf(UriInterface::class, $uri);
            $this->assertEquals(['Content-Type' => 'application/json'], $headers);
            $this->assertNull($body);

            $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

            return $request;
        });
        $this->httpClient->expects($this->never())->method('sendRequest');
        $this->messageValidator->expects($this->never())->method('validateResponse');
        $this->messageValidator->expects($this->once())->method('hasViolations')->willReturn(true);
        $this->messageValidator->expects($this->once())->method('getViolations')->willReturnCallback(function () {
            $violation = $this->getMockBuilder(ConstraintViolation::class)->disableOriginalConstructor()->getMock();
            $violation->expects($this->once())->method('getProperty')->willReturn('foo');
            $violation->expects($this->once())->method('getMessage')->willReturn('required');
            $violation->expects($this->once())->method('getConstraint')->willReturn('bar');
            $violation->expects($this->once())->method('getLocation')->willReturn('query');

            return [$violation];
        });

        try {
            $service = $this->getApiService();
            $service->call('operationId', ['foo' => 'bar']);
        } catch (RequestViolations $e) {
            $this->assertEquals("Request constraint violations:\n[property]: foo\n[message]: required\n[constraint]: bar\n[location]: query\n\n", $e->getMessage());

            return;
        }
        $this->fail("This test must throw a RequestViolations Exception");
    }

    /** @test */
    public function itShouldNotReturnTheResponseOrAnyDataBecauseThereAreSomeViolationInResponse()
    {
        $this->uriTemplate->expects($this->once())->method('expand')->willReturnCallback(function ($pathTemplate, array $pathParameters) {
            $this->assertEquals('/foo/bar', $pathTemplate);
            $this->assertEquals([], $pathParameters);
        });

        $params = $this->getMockBuilder(Parameters::class)->disableOriginalConstructor()->getMock();
        $params->expects($this->once())->method('getIterator')->willReturn([]);
        $params->expects($this->once())->method('getByName')->willReturnCallback(function ($name) {
            $this->assertEquals('foo', $name);
            $param = $this->getMockBuilder(Parameter::class)->disableOriginalConstructor()->getMock();
            $param->expects($this->once())->method('getLocation')->willReturn('query');

            return $param;
        });

        $baseUri = $this->createMock(UriInterface::class);
        $baseUri->expects($this->once())->method('withPath')->willReturnCallback(function ($path) use ($baseUri) {
            $this->assertEquals("", $path);

            return $baseUri;
        });
        $baseUri->expects($this->once())->method('withQuery')->willReturnCallback(function ($query) use ($baseUri) {
            $this->assertEquals("foo=bar", $query);

            return $baseUri;
        });

        $requestDefinition = $this->getMockBuilder(RequestDefinition::class)->disableOriginalConstructor()->getMock();
        $requestDefinition->expects($this->once())->method('getContentTypes')->willReturn(['application/json']);
        $requestDefinition->expects($this->once())->method('getRequestParameters')->willReturn($params);
        $requestDefinition->expects($this->once())->method('getMethod')->willReturn('GET');
        $requestDefinition->expects($this->once())->method('getPathTemplate')->willReturn('/foo/bar');
        $requestDefinition->expects($this->never())->method('getResponseDefinition');

        $this->config['baseUri'] = 'https://somewhere.tld';
        $this->config['validateResponse'] = true;
        $this->config['validateRequest'] = false;
        $this->uriFactory->expects($this->exactly(1))->method('createUri')->with('https://somewhere.tld')->willReturn($baseUri);
        $this->schema->expects($this->exactly(1))->method('getRequestDefinition')->with('operationId')->willReturn($requestDefinition);
        $this->messageFactory->expects($this->once())->method('createRequest')->willReturnCallback(function ($method, $uri, $headers, $body) {
            $this->assertEquals('GET', $method);
            $this->assertInstanceOf(UriInterface::class, $uri);
            $this->assertEquals(['Content-Type' => 'application/json'], $headers);
            $this->assertNull($body);

            $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

            return $request;
        });
        $this->httpClient->expects($this->once())->method('sendRequest')->willReturnCallback(function ($request) {
            $this->assertInstanceOf(Request::class, $request);

            $response = $this->createMock(ResponseInterface::class);
            $response->expects($this->never())->method('getStatusCode');
            $response->expects($this->never())->method('getBody');

            return $response;
        });
        $this->messageValidator->expects($this->once())->method('validateResponse');
        $this->messageValidator->expects($this->once())->method('hasViolations')->willReturn(true);
        $this->messageValidator->expects($this->once())->method('getViolations')->willReturnCallback(function () {
            $violation = $this->getMockBuilder(ConstraintViolation::class)->disableOriginalConstructor()->getMock();
            $violation->expects($this->once())->method('getProperty')->willReturn('foo');
            $violation->expects($this->once())->method('getMessage')->willReturn('required');
            $violation->expects($this->once())->method('getConstraint')->willReturn('bar');
            $violation->expects($this->once())->method('getLocation')->willReturn('query');

            return [$violation];
        });

        try {
            $service = $this->getApiService();
            $service->call('operationId', ['foo' => 'bar']);
        } catch (ResponseViolations $e) {
            $this->assertEquals("Request constraint violations:\n[property]: foo\n[message]: required\n[constraint]: bar\n[location]: query\n\n", $e->getMessage());

            return;
        }
        $this->fail("This test must throw a ResponseViolations Exception");
    }

    /**
     * @throws \Assert\AssertionFailedException
     *
     * @return ApiService
     */
    private function getApiService(): ApiService
    {
        return new ApiService(
            $this->uriFactory,
            $this->uriTemplate,
            $this->httpClient,
            $this->messageFactory,
            $this->schema,
            $this->messageValidator,
            $this->serializer,
            $this->config
        );
    }
}
