<?php
namespace ElevenLabs\Api\Service\Collection\Provider;

use ElevenLabs\Api\Service\Collection\Pagination;
use Http\Message\UriFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class HeaderCollectionTest extends TestCase
{
    /** @test */
    public function itProvideACollection()
    {
        $linkHeader = '<http://domain.tld/foo?page=1&per_page=10>; rel="first",'
            . '<http://domain.tld/foo?page=20&per_page=10>; rel="last",'
            . '<http://domain.tld/foo?page=4&per_page=10>; rel="next",'
            . '<http://domain.tld/foo?page=2&per_page=10>; rel="prev"';

        $response = $this->prophesize(ResponseInterface::class);
        $response->getHeaderLine('Link')->willReturn($linkHeader);
        $response->getHeaderLine('X-Pagination-Page')->willReturn('3');
        $response->getHeaderLine('X-Pagination-Per-Page')->willReturn('10');
        $response->getHeaderLine('X-Pagination-Total-Entries')->willReturn('200');
        $response->getHeaderLine('X-Pagination-Total-Pages')->willReturn('20');
        $response->hasHeader(Argument::any())->willReturn(true);
        $response->getBody()->willReturn($this->prophesize(StreamInterface::class));

        $uriFactory = $this->prophesize(UriFactory::class);
        $uriFactory->createUri('http://domain.tld/foo?page=1&per_page=10')->willReturn($this->getUriMock('http://domain.tld/foo?page=1&per_page=10'));
        $uriFactory->createUri('http://domain.tld/foo?page=20&per_page=10')->willReturn($this->getUriMock('http://domain.tld/foo?page=1&per_page=10'));
        $uriFactory->createUri('http://domain.tld/foo?page=4&per_page=10')->willReturn($this->getUriMock('http://domain.tld/foo?page=4&per_page=10'));
        $uriFactory->createUri('http://domain.tld/foo?page=2&per_page=10')->willReturn($this->getUriMock('http://domain.tld/foo?page=2&per_page=10'));

        $provider = new HeaderCollection($uriFactory->reveal());
        $collection = $provider->getCollection($response->reveal(), []);

        self::assertInstanceOf(\Traversable::class, $collection);
        self::assertInstanceOf(Pagination::class, $collection);
        self::assertSame(3, $collection->getPage());
        self::assertSame(10, $collection->getPerPage());
        self::assertSame(20, $collection->getTotalPages());
        self::assertSame(200, $collection->getTotalEntries());
    }

    private function getUriMock($uriString)
    {
        $uri = $this->prophesize(UriInterface::class);
        $uri->__toString()->willReturn($uriString);

        return $uri;
    }
}