<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Tests\Pagination;

use ElevenLabs\Api\Service\Pagination\Pagination;
use ElevenLabs\Api\Service\Pagination\PaginationLinks;
use PHPUnit\Framework\TestCase;

/**
 * Class PaginationTest.
 */
class PaginationTest extends TestCase
{
    /** @test */
    public function itProvidePaginationMetadata()
    {
        $pagination = new Pagination(2, 20, 100, 5);

        $this->assertSame(2, $pagination->getPage());
        $this->assertSame(20, $pagination->getPerPage());
        $this->assertSame(100, $pagination->getTotalItems());
        $this->assertSame(5, $pagination->getTotalPages());
        $this->assertFalse($pagination->hasLinks());
    }

    /** @test */
    public function itProvidePaginationLinks()
    {
        $links = $this->prophesize(PaginationLinks::class);
        $pagination = new Pagination(2, 20, 100, 5, $links->reveal());

        $this->assertInstanceOf(PaginationLinks::class, $pagination->getLinks());
    }
}
