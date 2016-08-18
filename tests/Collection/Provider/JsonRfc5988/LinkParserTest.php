<?php
namespace ElevenLabs\Swagger\Http\Collection\RFC5988;


use ElevenLabs\Api\Service\Collection\Provider\JsonRfc5988\LinkParser;
use PHPUnit\Framework\TestCase;

class LinkParserTest extends TestCase
{
    /** @test */
    public function itShouldParseALinkHeader()
    {
        $header = '<http://domain.tld/foo?page=1&per_page=10>; rel="first",'
            . '<http://domain.tld/foo?page=20&per_page=10>; rel="last",'
            . '<http://domain.tld/foo?page=4&per_page=10>; rel="next",'
            . '<http://domain.tld/foo?page=2&per_page=10>; rel="prev"';

        $expected = [
            'first' => 'http://domain.tld/foo?page=1&per_page=10',
            'last' => 'http://domain.tld/foo?page=20&per_page=10',
            'next' => 'http://domain.tld/foo?page=4&per_page=10',
            'prev' => 'http://domain.tld/foo?page=2&per_page=10',
        ];

        $actual = LinkParser::parse($header);

        self::assertEquals($expected, $actual);
    }
}