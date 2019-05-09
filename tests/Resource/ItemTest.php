<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Resource\Tests;

use ElevenLabs\Api\Service\Resource\Item;
use ElevenLabs\Api\Service\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class ItemTest.
 */
class ItemTest extends TestCase
{
    /** @test */
    public function itIsAResource()
    {
        $resource = new Item([], [], []);

        $this->assertInstanceOf(ResourceInterface::class, $resource);
    }

    /** @test */
    public function itProvideDataAndMeta()
    {
        $data = ['foo' => 'bar'];
        $meta = ['headers' => ['bat' => 'baz']];
        $resource = new Item($data, $meta, $data);

        $this->assertSame($data, $resource->getData());
        $this->assertSame($meta, $resource->getMeta());
        $this->assertSame($data, $resource->getBody());
    }
}
