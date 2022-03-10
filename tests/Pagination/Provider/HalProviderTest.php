<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Tests\Pagination\Provider;

use ElevenLabs\Api\Definition\ResponseDefinition;
use ElevenLabs\Api\Service\Pagination\Pagination;
use ElevenLabs\Api\Service\Pagination\PaginationLinks;
use ElevenLabs\Api\Service\Pagination\Provider\HalProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * Class HalProviderTest.
 */
class HalProviderTest extends TestCase
{
    /**
     * @var ResponseInterface|MockObject
     */
    private $response;

    /**
     * @var ResponseDefinition|MockObject
     */
    private $responseDefinition;

    /**
     * @var array
     */
    private $config;

    public function setUp()
    {
        $this->response = $this->createMock(ResponseInterface::class);
        $this->responseDefinition = $this->createMock(ResponseDefinition::class);
        $this->config = [
            'page' => '_links.self.href.page',
            'perPage' => 'itemsPerPage',
            'totalItems' => 'totalItems',
            'totalPages' => '_links.last.href.page',
        ];
    }

    /** @test */
    public function itNotSupportPaginationWhenResponseIsEmpty()
    {
        $data = [];
        $provider = new HalProvider($this->config);
        $this->assertFalse($provider->supportPagination($data, $this->response, $this->responseDefinition));
    }

    /** @test */
    public function itNotSupportPaginationWhenThereAreNotLinkField()
    {
        $data = [
            [
                '_links' => [
                    'self' => ['href' => '/bar?page=1'],
                    'first' => ['href' => '/bar?page=1'],
                    'last' => ['href' => '/bar?page=2'],
                    'next' => ['href' => '/bar?page=2'],
                ],
                'itemsPerPage' => 10,
                'totalItems' => 20,
                '_embedded' => [
                    'item' => [
                        [
                            '_links' => ['self' => ['href' => '/bar/emilie-stroman']],
                            '_embedded' => [
                                'category' => [
                                    '_links' => ['self' => ['href' => '/foo/foo-1']],
                                    'title' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                                    'slug' => 'lorem-ipsum-dolor-sit-amet-consectetur-adipiscing-elit',
                                    'dateCreated' => "2019-03-29T21:58:14+00:00",
                                    'dateModified' => "2019-03-29T21:58:14+00:00",
                                ],
                            ],
                            'nickName' => 'Emilie STROMAN',
                            'slug' => 'emilie-stroman',
                            'score' => 0,
                            'dateCreated' => '2019-03-29T21:58:16+00:00',
                            'dateModified' => '2019-03-29T22:40:56+00:00',
                        ],
                    ],
                ],
            ],
        ];
        $provider = new HalProvider($this->config);
        $this->assertFalse($provider->supportPagination($data, $this->response, $this->responseDefinition));
    }

    /**
     * @test
     *
     * @param array $links
     *
     * @dataProvider dataProviderItNotSupportPaginationWhenThereAreNotLinks
     */
    public function itNotSupportPaginationWhenThereAreNotLinks(array $links)
    {
        $data = [
            '_links' => $links,
            'itemsPerPage' => 10,
            'totalItems' => 20,
            '_embedded' => ['item' => []],
        ];
        $provider = new HalProvider($this->config);
        $this->assertFalse($provider->supportPagination($data, $this->response, $this->responseDefinition));
    }

    /**
     * @return array
     */
    public function dataProviderItNotSupportPaginationWhenThereAreNotLinks(): array
    {
        return [
            [
                [
                    'first' => ['href' => 'http://example.org?page=1'],
                    'last' => ['href' => 'http://example.org?page=2'],
                ],
            ],
            [
                [
                    'self' => ['href' => 'http://example.org?page=1'],
                    'first' => ['href' => 'http://example.org?page=1'],
                ],
            ],
            [
                [
                    'last' => ['href' => 'http://example.org?page=2'],
                ],
            ],
        ];
    }

    /** @test */
    public function itNotSupportPaginationWhenThereAreNotEmbeddedField()
    {
        $data = [
            '_links' => [
                'self' => ['href' => 'http://example.org?page=1'],
                'first' => ['href' => 'http://example.org?page=1'],
                'last' => ['href' => 'http://example.org?page=2'],
            ],
            'itemsPerPage' => 10,
            'totalItems' => 20,
        ];

        $provider = new HalProvider($this->config);
        $this->assertFalse($provider->supportPagination($data, $this->response, $this->responseDefinition));
    }

    /** @test */
    public function itSupportPaginationWhenThereAreNoData()
    {
        $data = [
            'itemsPerPage' => 10,
            'totalItems' => 20,
            '_links' => [
                'self' => ['href' => 'http://example.org?page=1'],
                'first' => ['href' => 'http://example.org?page=1'],
                'last' => ['href' => 'http://example.org?page=2'],
            ],
            '_embedded' => ['item' => []],
        ];

        $provider = new HalProvider($this->config);
        $this->assertTrue($provider->supportPagination($data, $this->response, $this->responseDefinition));
    }

    /**
     * @test
     *
     * @dataProvider dataProviderItHavePagination
     *
     * @param array $data
     * @param array $expected
     */
    public function itHavePagination(array $data, array $expected)
    {
        $provider = new HalProvider($this->config);
        $pagination = $provider->getPagination($data, $this->response, $this->responseDefinition);

        $this->assertInstanceOf(Pagination::class, $pagination);
        $this->assertSame($expected['page'], $pagination->getPage());
        $this->assertSame($expected['perPage'], $pagination->getPerPage());
        $this->assertSame($expected['totalItems'], $pagination->getTotalItems());
        $this->assertSame($expected['totalPages'], $pagination->getTotalPages());

        $this->assertInstanceOf(PaginationLinks::class, $pagination->getLinks());

        $links = $pagination->getLinks();
        $this->assertSame($expected['first'], $links->getFirst());
        $this->assertSame($expected['last'], $links->getLast());
        $this->assertSame($expected['next'], $links->getNext());
        $this->assertSame($expected['prev'], $links->getPrev());

        $this->assertEquals(
            [
                'category',
                'nickName',
                'slug',
                'score',
                'dateCreated',
                'dateModified',
            ],
            array_keys($data[0])
        );
    }

    /**
     * @return array
     */
    public function dataProviderItHavePagination(): array
    {
        return [
            [
                json_decode(file_get_contents(realpath(sprintf('%s/../../fixtures/pagination/pagination_with_embed_self_page.json', __DIR__))), true),
                [
                    'page' => 1,
                    'perPage' => 2,
                    'totalItems' => 2,
                    'totalPages' => 1,
                    'first' => '/videos',
                    'last' => '/videos',
                    'next' => null,
                    'prev' => null,
                ],
            ],
            [
                json_decode(file_get_contents(realpath(sprintf('%s/../../fixtures/pagination/pagination_with_embed_first_page.json', __DIR__))), true),
                [
                    'page' => 1,
                    'perPage' => 2,
                    'totalItems' => 4,
                    'totalPages' => 2,
                    'first' => '/videos?page=1',
                    'last' => '/videos?page=2',
                    'next' => '/videos?page=2',
                    'prev' => null,
                ],
            ],
            [
                json_decode(file_get_contents(realpath(sprintf('%s/../../fixtures/pagination/pagination_with_embed_second_page.json', __DIR__))), true),
                [
                    'page' => 2,
                    'perPage' => 2,
                    'totalItems' => 4,
                    'totalPages' => 2,
                    'first' => '/videos?page=1',
                    'last' => '/videos?page=2',
                    'next' => null,
                    'prev' => '/videos?page=1',
                ],
            ],
            [
                json_decode(file_get_contents(realpath(sprintf('%s/../../fixtures/pagination/pagination_with_embed_last_page.json', __DIR__))), true),
                [
                    'page' => 2,
                    'perPage' => 3,
                    'totalItems' => 10,
                    'totalPages' => 4,
                    'first' => '/videos?page=1',
                    'last' => '/videos?page=4',
                    'next' => '/videos?page=3',
                    'prev' => '/videos?page=1',
                ],
            ],
        ];
    }
}
