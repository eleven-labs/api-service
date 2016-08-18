<?php
namespace ElevenLabs\Api\Service\Collection\Provider;

use ElevenLabs\Api\Service\Decoder\Decoder;
use GuzzleHttp\Psr7\Response;
use Http\Message\UriFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class JsonRfc5988Test extends TestCase
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
        $response->hasHeader(Argument::any())->willReturn(true);
        $response->getHeaderLine('X-Pagination-Page')->willReturn('3');
        $response->getHeaderLine('X-Pagination-Per-Page')->willReturn('10');
        $response->getHeaderLine('X-Pagination-Total-Items')->willReturn('200');
        $response->getHeaderLine('X-Pagination-Total-Pages')->willReturn('20');
        $response->getBody()->willReturn($this->prophesize(StreamInterface::class));

        $uriFactory = $this->prophesize(UriFactory::class);
        $uriFactory->createUri('http://domain.tld/foo?page=1&per_page=10')->willReturn($this->getUriMock('http://domain.tld/foo?page=1&per_page=10'));
        $uriFactory->createUri('http://domain.tld/foo?page=20&per_page=10')->willReturn($this->getUriMock('http://domain.tld/foo?page=1&per_page=10'));
        $uriFactory->createUri('http://domain.tld/foo?page=4&per_page=10')->willReturn($this->getUriMock('http://domain.tld/foo?page=4&per_page=10'));
        $uriFactory->createUri('http://domain.tld/foo?page=2&per_page=10')->willReturn($this->getUriMock('http://domain.tld/foo?page=2&per_page=10'));

        $decoder = $this->prophesize(Decoder::class);
        $decoder->decode(Argument::any())->willReturn([]);

        $provider = new JsonRfc5988($uriFactory->reveal(), $decoder->reveal());
        $provider->getCollection($response->reveal());
    }

    private function getUriMock($uriString)
    {
        $uri = $this->prophesize(UriInterface::class);
        $uri->__toString()->willReturn($uriString);

        return $uri;
    }
}