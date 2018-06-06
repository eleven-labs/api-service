<?php

namespace ElevenLabs\Api\Service\Pagination\Provider;

use ElevenLabs\Api\Definition\ResponseDefinition;
use ElevenLabs\Api\Service\Pagination\Pagination;
use ElevenLabs\Api\Service\Pagination\PaginationLinks;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * Class HateoasPaginationTest
 *
 * @package ElevenLabs\Api\Service\Pagination\Provider
 */
class HateoasPaginationTest extends TestCase
{
    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var ResponseDefinition
     */
    private $responseDefinition;

    /**
     * @var array
     */
    private $fields;

    public function setUp()
    {
        $this->response = $this->createMock(ResponseInterface::class);
        $this->responseDefinition = $this->createMock(ResponseDefinition::class);
        $this->fields = [
            HateoasPagination::DEFAULT_PAGINATION_VALUE['page'] => "page",
            HateoasPagination::DEFAULT_PAGINATION_VALUE['perPage'] => 'perPage',
            HateoasPagination::DEFAULT_PAGINATION_VALUE['totalItems'] => 'totalItems',
            HateoasPagination::DEFAULT_PAGINATION_VALUE['totalPages'] => 'totalPages'
        ];
    }

    /** @test */
    public function itNotHavePaginationWhenLinkFieldIsEmpty()
    {
        $data = [
            'page' => 1,
            'perPage' => 10,
            'totalItems' => 20,
            'totalPages' => 2,
            '_links' => [],
            '_embedded' => ['item' => []],
        ];
        $provider = new HateoasPagination($this->fields);
        $pagination = $provider->getPagination($data, $this->response, $this->responseDefinition);
        $this->assertInstanceOf(Pagination::class, $pagination);
        $this->assertEquals(1, $pagination->getPage());
        $this->assertEquals(10, $pagination->getPerPage());
        $this->assertEquals(20, $pagination->getTotalItems());
        $this->assertEquals(2, $pagination->getTotalPages());
        $this->assertNull($pagination->getLinks());
        $this->assertEquals([], $data);
    }

    /** @test */
    public function itHavePaginationWhenLinkFieldIsNotEmpty()
    {
        $data = [
            'page' => 1,
            'perPage' => 10,
            'totalItems' => 20,
            'totalPages' => 2,
            '_links' => [
                'self' => ['href' => 'http://example.org/self'],
                'first' => ['href' => 'http://example.org/first'],
                'last' => ['href' => 'http://example.org/last'],
            ],
            '_embedded' => ['item' => []],
        ];
        $provider = new HateoasPagination($this->fields);
        $pagination = $provider->getPagination($data, $this->response, $this->responseDefinition);
        $this->assertInstanceOf(Pagination::class, $pagination);
        $this->assertEquals(1, $pagination->getPage());
        $this->assertEquals(10, $pagination->getPerPage());
        $this->assertEquals(20, $pagination->getTotalItems());
        $this->assertEquals(2, $pagination->getTotalPages());
        $this->assertInstanceOf(PaginationLinks::class, $pagination->getLinks());
        $this->assertEquals('http://example.org/first', $pagination->getLinks()->getFirst());
        $this->assertEquals('http://example.org/last', $pagination->getLinks()->getLast());
        $this->assertFalse($pagination->getLinks()->hasNext());
        $this->assertFalse($pagination->getLinks()->hasPrev());
        $this->assertEquals([], $data);
    }

    /** @test */
    public function itNotSupportPaginationWhenResponseIsEmpty()
    {
        $data = [];
        $provider = new HateoasPagination($this->fields);
        $this->assertFalse($provider->supportPagination($data, $this->response, $this->responseDefinition));
    }

    /** @test */
    public function itNotSupportPaginationWhenThereAreNotLinkField()
    {
        $data = [
            'page' => 1,
            'perPage' => 10,
            'totalItems' => 20,
            'totalPages' => 2,
        ];
        $provider = new HateoasPagination($this->fields);
        $this->assertFalse($provider->supportPagination($data, $this->response, $this->responseDefinition));
    }

    /**
     * @param array $links
     *
     * @dataProvider dataProviderItNotSupportPaginationWhenThereAreNotLinks
     * @test
     */
    public function itNotSupportPaginationWhenThereAreNotLinks(array $links)
    {
        $data = [
            'page' => 1,
            'perPage' => 10,
            'totalItems' => 20,
            'totalPages' => 2,
            '_links' => $links,
            '_embedded' => ['item' => []],
        ];
        $provider = new HateoasPagination($this->fields);
        $this->assertFalse($provider->supportPagination($data, $this->response, $this->responseDefinition));
    }

    /** @test */
    public function itNotSupportPaginationWhenThereAreNotEmbeddedField()
    {
        $data = [
            'page' => 1,
            'perPage' => 10,
            'totalItems' => 20,
            'totalPages' => 2,
            '_links' => [
                'self' => ['href' => 'http://example.org'],
                'first' => ['href' => 'http://example.org'],
                'last' => ['href' => 'http://example.org'],
            ],
        ];
        $provider = new HateoasPagination($this->fields);
        $this->assertFalse($provider->supportPagination($data, $this->response, $this->responseDefinition));
    }

    /** @test */
    public function itSupportPaginationWhenThereAreNoData()
    {
        $data = [
            'page' => 1,
            'perPage' => 10,
            'totalItems' => 20,
            'totalPages' => 2,
            '_links' => [
                'self' => ['href' => 'http://example.org'],
                'first' => ['href' => 'http://example.org'],
                'last' => ['href' => 'http://example.org'],
            ],
            '_embedded' => ['item' => []],
        ];
        $provider = new HateoasPagination($this->fields);
        $this->assertTrue($provider->supportPagination($data, $this->response, $this->responseDefinition));
    }

    public function dataProviderItNotSupportPaginationWhenThereAreNotLinks()
    {
        return [
            [
                [
                    'first' => ['href' => 'http://example.org'],
                    'last' => ['href' => 'http://example.org'],
                ],
                [
                    'self' => ['href' => 'http://example.org'],
                    'last' => ['href' => 'http://example.org'],
                ],
                [
                    'last' => ['href' => 'http://example.org'],
                ]
            ]
        ];
    }
}
