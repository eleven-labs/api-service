<?php
namespace ElevenLabs\Api\Service\Pagination;

use PHPUnit\Framework\TestCase;

class PaginationTest extends TestCase
{
    /** @test */
    public function itProvidePaginationMetadata()
    {
        $pagination = new Pagination(2, 20, 100, 5);

        assertThat($pagination->getPage(), equalTo(2));
        assertThat($pagination->getPerPage(), equalTo(20));
        assertThat($pagination->getTotalItems(), equalTo(100));
        assertThat($pagination->getTotalPages(), equalTo(5));
        assertThat($pagination->hasLinks(), isFalse());
    }

    /** @test */
    public function itProvidePaginationLinks()
    {
        $links = $this->prophesize(PaginationLinks::class);
        $pagination = new Pagination(2, 20, 100, 5, $links->reveal());

        assertThat($pagination->getLinks(), isInstanceOf(PaginationLinks::class));
    }
}
