<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Denormalizer\Tests;

use ElevenLabs\Api\Definition\ResponseDefinition;
use ElevenLabs\Api\Service\Denormalizer\ResourceDenormalizer;
use ElevenLabs\Api\Service\Pagination\Pagination;
use ElevenLabs\Api\Service\Pagination\Provider\PaginationProviderInterface;
use ElevenLabs\Api\Service\Resource\Collection;
use ElevenLabs\Api\Service\Resource\Item;
use ElevenLabs\Api\Service\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class ResourceDenormalizerTest.
 */
class ResourceDenormalizerTest extends TestCase
{
    /** @test */
    public function itShouldSupportResourceType()
    {
        $paginationProvider = $this->prophesize(PaginationProviderInterface::class);
        $denormalizer = new ResourceDenormalizer($paginationProvider->reveal());

        $this->assertTrue($denormalizer->supportsDenormalization([], ResourceInterface::class));
    }

    /** @test */
    public function itShouldProvideAResourceOfTypeItem()
    {
        $response = $this->prophesize(ResponseInterface::class);

        $request = $this->prophesize(RequestInterface::class);

        $responseDefinition = $this->prophesize(ResponseDefinition::class);
        $responseDefinition->hasBodySchema()->willReturn(true);
        $responseDefinition->getBodySchema()->willReturn((object) ['type' => 'object']);

        $paginationProvider = $this->prophesize(PaginationProviderInterface::class);
        $paginationProvider->supportPagination()->shouldNotBeCalled();

        $denormalizer = new ResourceDenormalizer($paginationProvider->reveal());
        $resource = $denormalizer->denormalize(
            ['foo' => 'bar'],
            ResourceInterface::class,
            null,
            [
                'response' => $response->reveal(),
                'responseDefinition' => $responseDefinition->reveal(),
                'request' => $request->reveal(),
            ]
        );

        $this->assertInstanceOf(Item::class, $resource);
    }

    /** @test */
    public function itShouldThrowAnExceptionWhenNoResponseSchemaIsDefinedInTheResponseDefinition()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Cannot transform the response into a resource. '.
            'You need to provide a schema for response 200 in GET /foo'
        );

        $requestPath = '/foo';

        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn($requestPath);

        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(200);

        $request = $this->prophesize(RequestInterface::class);
        $request->getUri()->willreturn($uri);
        $request->getMethod()->willReturn('GET');

        $responseDefinition = $this->prophesize(ResponseDefinition::class);
        $responseDefinition->hasBodySchema()->willReturn(false);

        $paginationProvider = $this->prophesize(PaginationProviderInterface::class);

        $denormalizer = new ResourceDenormalizer($paginationProvider->reveal());
        $denormalizer->denormalize(
            [],
            ResourceInterface::class,
            null,
            [
                'response' => $response->reveal(),
                'responseDefinition' => $responseDefinition->reveal(),
                'request' => $request->reveal(),
            ]
        );
    }

    /** @test */
    public function itShouldProvideAResourceOfTypeCollection()
    {
        $data = [
            ['foo' => 'bar'],
        ];

        $response = $this->prophesize(ResponseInterface::class);

        $request = $this->prophesize(RequestInterface::class);

        $responseDefinition = $this->prophesize(ResponseDefinition::class);
        $responseDefinition->hasBodySchema()->willReturn(true);
        $responseDefinition->getBodySchema()->willReturn((object) ['type' => 'array']);

        $paginationProvider = $this->prophesize(PaginationProviderInterface::class);
        $paginationProvider->supportPagination($data, $response, $responseDefinition)->willReturn(false);

        $denormalizer = new ResourceDenormalizer($paginationProvider->reveal());
        $resource = $denormalizer->denormalize(
            $data,
            ResourceInterface::class,
            null,
            [
                'response' => $response->reveal(),
                'responseDefinition' => $responseDefinition->reveal(),
                'request' => $request->reveal(),
            ]
        );

        $this->assertInstanceOf(Collection::class, $resource);
    }

    /** @test */
    public function itShouldProvideAResourceOfTypeCollectionWithPagination()
    {
        $data = [
            ['foo' => 'bar'],
        ];

        $response = $this->prophesize(ResponseInterface::class);

        $request = $this->prophesize(RequestInterface::class);

        $responseDefinition = $this->prophesize(ResponseDefinition::class);
        $responseDefinition->hasBodySchema()->willReturn(true);
        $responseDefinition->getBodySchema()->willReturn((object) ['type' => 'array']);

        $pagination = $this->prophesize(Pagination::class);

        $paginationProvider = $this->prophesize(PaginationProviderInterface::class);
        $paginationProvider->supportPagination($data, $response, $responseDefinition)->willReturn(true);
        $paginationProvider->getPagination($data, $response, $responseDefinition)->willReturn($pagination);

        $denormalizer = new ResourceDenormalizer($paginationProvider->reveal());
        $resource = $denormalizer->denormalize(
            $data,
            ResourceInterface::class,
            null,
            [
                'response' => $response->reveal(),
                'responseDefinition' => $responseDefinition->reveal(),
                'request' => $request->reveal(),
            ]
        );

        $this->assertInstanceOf(Collection::class, $resource);
        $this->assertSame($pagination->reveal(), $resource->getPagination());
    }

    /** @test */
    public function itCanExtractTypeFromAnAllOfSchema()
    {
        $jsonSchema = (object) [
            'allOf' => [
                (object) ['type' => 'object'],
            ],
        ];

        $response = $this->prophesize(ResponseInterface::class);

        $request = $this->prophesize(RequestInterface::class);

        $responseDefinition = $this->prophesize(ResponseDefinition::class);
        $responseDefinition->hasBodySchema()->willReturn(true);
        $responseDefinition->getBodySchema()->willReturn($jsonSchema);

        $paginationProvider = $this->prophesize(PaginationProviderInterface::class);
        $paginationProvider->supportPagination()->shouldNotBeCalled();

        $denormalizer = new ResourceDenormalizer($paginationProvider->reveal());
        $resource = $denormalizer->denormalize(
            ['foo' => 'bar'],
            ResourceInterface::class,
            null,
            [
                'response' => $response->reveal(),
                'responseDefinition' => $responseDefinition->reveal(),
                'request' => $request->reveal(),
            ]
        );

        assertThat($resource, isInstanceOf(Item::class));
        $this->assertSame(['headers' => null], $resource->getMeta());
    }

    /** @test */
    public function itThrowAnExceptionWhenSchemaTypeCannotBeExtracted()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot extract type from schema');

        $jsonSchema = (object) ['invalid' => 'invalid'];

        $response = $this->prophesize(ResponseInterface::class);
        $request = $this->prophesize(RequestInterface::class);

        $responseDefinition = $this->prophesize(ResponseDefinition::class);
        $responseDefinition->hasBodySchema()->willReturn(true);
        $responseDefinition->getBodySchema()->willReturn($jsonSchema);

        $paginationProvider = $this->prophesize(PaginationProviderInterface::class);
        $paginationProvider->supportPagination()->shouldNotBeCalled();

        $denormalizer = new ResourceDenormalizer($paginationProvider->reveal());
        $denormalizer->denormalize(
            ['foo' => 'bar'],
            ResourceInterface::class,
            null,
            [
                'response' => $response->reveal(),
                'responseDefinition' => $responseDefinition->reveal(),
                'request' => $request->reveal(),
            ]
        );
    }
}
