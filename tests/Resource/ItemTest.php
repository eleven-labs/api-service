<?php
namespace ElevenLabs\Api\Service\Resource;

use PHPUnit\Framework\TestCase;

class ItemTest extends TestCase
{
    /** @test */
    public function itIsAResource()
    {
        $resource = new Item([], []);

        assertThat($resource, isInstanceOf(Resource::class));
    }

    /** @test */
    public function itProvideDataAndMeta()
    {
        $data = ['foo' => 'bar'];
        $meta = ['headers' => ['bat' => 'baz']];
        $resource = new Item($data, $meta);

        assertThat($resource->getData(), equalTo($data));
        assertThat($resource->getMeta(), equalTo($meta));
    }
}
