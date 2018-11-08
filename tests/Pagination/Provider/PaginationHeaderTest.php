<?php
namespace ElevenLabs\Api\Service\Pagination\Provider;

use ElevenLabs\Api\Definition\ResponseDefinition;
use ElevenLabs\Api\Service\Pagination\Pagination;
use ElevenLabs\Api\Service\Pagination\PaginationLinks;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class PaginationHeaderTest extends TestCase
{
    /** @test */
    public function itShouldSupportPaginationHeader()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->getHeaderLine('X-Page')->willReturn('1');
        $response->getHeaderLine('X-Per-Page')->willReturn('10');
        $response->getHeaderLine('X-Total-Items')->willReturn('100');
        $response->getHeaderLine('X-Total-Pages')->willReturn('10');

        $definition = $this->prophesize(ResponseDefinition::class);
        $provider = new PaginationHeader();

        assertThat($provider->supportPagination([], $response->reveal(), $definition->reveal()), isTrue());
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

        assertThat($pagination, isInstanceOf(Pagination::class));
        assertThat($pagination->getPage(), self::equalTo(1));
        assertThat($pagination->getPerPage(), self::equalTo(10));
        assertThat($pagination->getTotalItems(), self::equalTo(100));
        assertThat($pagination->getTotalPages(), self::equalTo(10));
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

        assertThat($pagination, isInstanceOf(Pagination::class));
        assertThat($pagination->getPage(), self::equalTo(1));
        assertThat($pagination->getPerPage(), self::equalTo(10));
        assertThat($pagination->getTotalItems(), self::equalTo(100));
        assertThat($pagination->getTotalPages(), self::equalTo(10));
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

        assertThat($paginationLinks, isInstanceOf(PaginationLinks::class));
        assertThat($paginationLinks->getFirst(), equalTo('http://domain.tld?page=1'));
        assertThat($paginationLinks->getLast(), equalTo('http://domain.tld?page=10'));
        assertThat($paginationLinks->getNext(), equalTo('http://domain.tld?page=4'));
        assertThat($paginationLinks->getPrev(), equalTo('http://domain.tld?page=2'));
    }
}
