<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Tests\Resource;

use ElevenLabs\Api\Service\Pagination\Pagination;
use ElevenLabs\Api\Service\Resource\Collection;
use ElevenLabs\Api\Service\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class CollectionTest.
 */
class CollectionTest extends TestCase
{
    /** @test */
    public function itIsAResource()
    {
        $resource = new Collection([], []);

        assertThat($resource, isInstanceOf(ResourceInterface::class));
    }

    /** @test */
    public function itProvideDataAndMeta()
    {
        $data = [['foo' => 'bar']];
        $meta = ['headers' => ['bat' => 'baz']];
        $resource = new Collection($data, $meta);

        $this->assertSame($data, $resource->getData());
        $this->assertSame($meta, $resource->getMeta());
        $this->assertFalse($resource->hasPagination());
    }

    /** @test */
    public function itProvideAPagination()
    {
        $pagination = new Pagination(1, 1, 1, 1);
        $resource = new Collection([], [], $pagination);

        $this->assertSame($pagination, $resource->getPagination());
    }

    /** @test */
    public function itIsTraversable()
    {
        $data = [
            ['value' => 'foo'],
            ['value' => 'bar'],
        ];

        $resource = new Collection($data, []);

        $this->assertInstanceOf(\Traversable::class, $resource);
        $this->assertContains($data[0], $resource);
        $this->assertContains($data[1], $resource);
    }
}
