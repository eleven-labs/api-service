<?php

namespace ElevenLabs\Api\Service\Pagination;

use PHPUnit\Framework\TestCase;

/**
 * Class PaginationLinksTest.
 */
class PaginationLinksTest extends TestCase
{
    /** @test */
    public function itProvidePaginationFirstAndLastLinks()
    {
        $links = new PaginationLinks(
            'http://domain.tld?page=1',
            'http://domain.tld?page=5'
        );

        assertThat($links->getFirst(), equalTo('http://domain.tld?page=1'));
        assertThat($links->getLast(), equalTo('http://domain.tld?page=5'));
        assertThat($links->hasNext(), isFalse());
        assertThat($links->hasPrev(), isFalse());
    }

    /** @test */
    public function itCanProvidePaginationNextAndPrevLinks()
    {
        $links = new PaginationLinks(
            'http://domain.tld?page=1',
            'http://domain.tld?page=5',
            'http://domain.tld?page=3',
            'http://domain.tld?page=2'
        );

        assertThat($links->hasNext(), isTrue());
        assertThat($links->hasPrev(), isTrue());
        assertThat($links->getNext(), equalTo('http://domain.tld?page=3'));
        assertThat($links->getPrev(), equalTo('http://domain.tld?page=2'));
    }
}
