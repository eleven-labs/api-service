<?php
namespace ElevenLabs\Api\Service\Denormalizer;

use ElevenLabs\Api\Definition\ResponseDefinition;
use ElevenLabs\Api\Service\Pagination\Pagination;
use ElevenLabs\Api\Service\Pagination\PaginationProvider;
use ElevenLabs\Api\Service\Resource\Collection;
use ElevenLabs\Api\Service\Resource\Item;
use ElevenLabs\Api\Service\Resource\Resource;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class ResourceDenormalizerTest extends TestCase
{
    /** @test */
    public function itShouldSupportResourceType()
    {
        $paginationProvider = $this->prophesize(PaginationProvider::class);
        $denormalizer = new ResourceDenormalizer($paginationProvider->reveal());

        assertThat(
            $denormalizer->supportsDenormalization([], Resource::class),
            isTrue()
        );
    }
    /** @test */
    public function itShouldProvideAResourceOfTypeItem()
    {
        $response = $this->prophesize(ResponseInterface::class);

        $request = $this->prophesize(RequestInterface::class);

        $responseDefinition = $this->prophesize(ResponseDefinition::class);
        $responseDefinition->hasBodySchema()->willReturn(true);
        $responseDefinition->getBodySchema()->willReturn((object) ['type' => 'object']);

        $paginationProvider = $this->prophesize(PaginationProvider::class);
        $paginationProvider->supportPagination()->shouldNotBeCalled();

        $denormalizer = new ResourceDenormalizer($paginationProvider->reveal());
        $resource = $denormalizer->denormalize(
            ['foo' => 'bar'],
            Resource::class,
            null,
            [
                'response' => $response->reveal(),
                'responseDefinition' => $responseDefinition->reveal(),
                'request' => $request->reveal()
            ]
        );

        assertThat($resource, isInstanceOf(Item::class));
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

        $paginationProvider = $this->prophesize(PaginationProvider::class);

        $denormalizer = new ResourceDenormalizer($paginationProvider->reveal());
        $denormalizer->denormalize(
            [],
            Resource::class,
            null,
            [
                'response' => $response->reveal(),
                'responseDefinition' => $responseDefinition->reveal(),
                'request' => $request->reveal()
            ]
        );
    }

    /** @test */
    public function itShouldProvideAResourceOfTypeCollection()
    {
        $data = [
            ['foo' => 'bar']
        ];

        $response = $this->prophesize(ResponseInterface::class);

        $request = $this->prophesize(RequestInterface::class);

        $responseDefinition = $this->prophesize(ResponseDefinition::class);
        $responseDefinition->hasBodySchema()->willReturn(true);
        $responseDefinition->getBodySchema()->willReturn((object) ['type' => 'array']);

        $paginationProvider = $this->prophesize(PaginationProvider::class);
        $paginationProvider->supportPagination($data, $response, $responseDefinition)->willReturn(false);

        $denormalizer = new ResourceDenormalizer($paginationProvider->reveal());
        $resource = $denormalizer->denormalize(
            $data,
            Resource::class,
            null,
            [
                'response' => $response->reveal(),
                'responseDefinition' => $responseDefinition->reveal(),
                'request' => $request->reveal()
            ]
        );

        assertThat($resource, isInstanceOf(Collection::class));
    }

    /** @test */
    public function itShouldProvideAResourceOfTypeCollectionWithPagination()
    {
        $data = [
            ['foo' => 'bar']
        ];

        $response = $this->prophesize(ResponseInterface::class);

        $request = $this->prophesize(RequestInterface::class);

        $responseDefinition = $this->prophesize(ResponseDefinition::class);
        $responseDefinition->hasBodySchema()->willReturn(true);
        $responseDefinition->getBodySchema()->willReturn((object) ['type' => 'array']);

        $pagination = $this->prophesize(Pagination::class);

        $paginationProvider = $this->prophesize(PaginationProvider::class);
        $paginationProvider->supportPagination($data, $response, $responseDefinition)->willReturn(true);
        $paginationProvider->getPagination($data, $response, $responseDefinition)->willReturn($pagination);

        $denormalizer = new ResourceDenormalizer($paginationProvider->reveal());
        $resource = $denormalizer->denormalize(
            $data,
            Resource::class,
            null,
            [
                'response' => $response->reveal(),
                'responseDefinition' => $responseDefinition->reveal(),
                'request' => $request->reveal()
            ]
        );

        assertThat($resource, isInstanceOf(Collection::class));
        assertThat($resource->getPagination(), equalTo($pagination->reveal()));
    }

    /** @test */
    public function itCanExtractTypeFromAnAllOfSchema()
    {
        $jsonSchema = (object) [
            'allOf' => [
                (object) ['type'=> 'object'],
                (object) ['type'=> 'object'],
            ]
        ];

        $response = $this->prophesize(ResponseInterface::class);

        $request = $this->prophesize(RequestInterface::class);

        $responseDefinition = $this->prophesize(ResponseDefinition::class);
        $responseDefinition->hasBodySchema()->willReturn(true);
        $responseDefinition->getBodySchema()->willReturn($jsonSchema);

        $paginationProvider = $this->prophesize(PaginationProvider::class);
        $paginationProvider->supportPagination()->shouldNotBeCalled();

        $denormalizer = new ResourceDenormalizer($paginationProvider->reveal());
        $resource = $denormalizer->denormalize(
            ['foo' => 'bar'],
            Resource::class,
            null,
            [
                'response' => $response->reveal(),
                'responseDefinition' => $responseDefinition->reveal(),
                'request' => $request->reveal()
            ]
        );

        assertThat($resource, isInstanceOf(Item::class));
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

        $paginationProvider = $this->prophesize(PaginationProvider::class);
        $paginationProvider->supportPagination()->shouldNotBeCalled();

        $denormalizer = new ResourceDenormalizer($paginationProvider->reveal());
        $resource = $denormalizer->denormalize(
            ['foo' => 'bar'],
            Resource::class,
            null,
            [
                'response' => $response->reveal(),
                'responseDefinition' => $responseDefinition->reveal(),
                'request' => $request->reveal()
            ]
        );
    }
}