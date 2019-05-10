<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Tests\Pagination\Provider;

use ElevenLabs\Api\Definition\ResponseDefinition;
use ElevenLabs\Api\Service\Pagination\Pagination;
use ElevenLabs\Api\Service\Pagination\PaginationLinks;
use ElevenLabs\Api\Service\Pagination\Provider\PaginationHeader;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * Class PaginationHeaderTest.
 */
class PaginationHeaderTest extends TestCase
{
    /** @test */
    public function itShouldSupportPaginationHeader()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->exactly(4))->method('getHeaderLine')->willReturnCallback(function ($name) {
            if ('X-Page' === $name) {
                return '1';
            }
            if ('X-Per-Page' === $name) {
                return '10';
            }
            if ('X-Total-Items' === $name) {
                return '100';
            }
            if ('X-Total-Pages' === $name) {
                return '10';
            }
        });

        $definition = $this->prophesize(ResponseDefinition::class);
        $provider = new PaginationHeader();

        $this->assertTrue($provider->supportPagination([], $response, $definition->reveal()));
    }

    /** @test */
    public function itProvidePaginationUsingResponseHeaders()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->hasHeader('Link')->willReturn(false);
        $response->getHeaderLine('X-Page')->willReturn('1');
        $response->getHeaderLine('X-Per-Page')->willReturn('10');
        $response->getHeaderLine('X-Total-Items')->willReturn('100');
        $response->getHeaderLine('X-Total-Pages')->willReturn('10');

        $data = [];

        $definition = $this->prophesize(ResponseDefinition::class);

        $provider = new PaginationHeader();
        $pagination = $provider->getPagination($data, $response->reveal(), $definition->reveal());

        $this->assertInstanceOf(Pagination::class, $pagination);
        $this->assertSame(1, $pagination->getPage());
        $this->assertSame(10, $pagination->getPerPage());
        $this->assertSame(100, $pagination->getTotalItems());
        $this->assertSame(10, $pagination->getTotalPages());
    }

    /** @test */
    public function itAllowPaginationHeaderKeyOverride()
    {
        $config = [
            'page' => 'X-Pagination-Page',
            'perPage' => 'X-Pagination-Per-Page',
            'totalItems' => 'X-Pagination-Total-Items',
            'totalPages' => 'X-Pagination-Total-Pages',
        ];

        $data = [];

        $response = $this->prophesize(ResponseInterface::class);
        $response->hasHeader('Link')->willReturn(false);
        $response->getHeaderLine('X-Pagination-Page')->willReturn('1');
        $response->getHeaderLine('X-Pagination-Per-Page')->willReturn('10');
        $response->getHeaderLine('X-Pagination-Total-Items')->willReturn('100');
        $response->getHeaderLine('X-Pagination-Total-Pages')->willReturn('10');

        $definition = $this->prophesize(ResponseDefinition::class);

        $provider = new PaginationHeader($config);
        $pagination = $provider->getPagination($data, $response->reveal(), $definition->reveal());

        $this->assertInstanceOf(Pagination::class, $pagination);
        $this->assertSame(1, $pagination->getPage());
        $this->assertSame(10, $pagination->getPerPage());
        $this->assertSame(100, $pagination->getTotalItems());
        $this->assertSame(10, $pagination->getTotalPages());
    }

    /** @test */
    public function itCanProvidePaginationLinks()
    {
        $linkHeader = [
            '<http://domain.tld?page=1>; rel="first"',
            '<http://domain.tld?page=10>; rel="last"',
            '<http://domain.tld?page=4>; rel="next"',
            '<http://domain.tld?page=2>; rel="prev"',
        ];

        $response = $this->prophesize(ResponseInterface::class);
        $response->getHeaderLine('X-Page')->willReturn('1');
        $response->getHeaderLine('X-Per-Page')->willReturn('10');
        $response->getHeaderLine('X-Total-Items')->willReturn('100');
        $response->getHeaderLine('X-Total-Pages')->willReturn('10');
        $response->hasHeader('Link')->willReturn(true);
        $response->getHeader('Link')->willReturn($linkHeader);

        $data = [];

        $definition = $this->prophesize(ResponseDefinition::class);

        $provider = new PaginationHeader();
        $pagination = $provider->getPagination($data, $response->reveal(), $definition->reveal());

        $paginationLinks = $pagination->getLinks();

        $this->assertInstanceOf(PaginationLinks::class, $paginationLinks);
        $this->assertSame('http://domain.tld?page=1', $paginationLinks->getFirst());
        $this->assertSame('http://domain.tld?page=10', $paginationLinks->getLast());
        $this->assertSame('http://domain.tld?page=4', $paginationLinks->getNext());
        $this->assertSame('http://domain.tld?page=2', $paginationLinks->getPrev());
    }
}
