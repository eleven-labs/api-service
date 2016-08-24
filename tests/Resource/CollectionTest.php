<?php
/**
 * Created by PhpStorm.
 * User: guillem
 * Date: 24/08/2016
 * Time: 15:55
 */

namespace ElevenLabs\Api\Service\Resource;


use ElevenLabs\Api\Service\Pagination\Pagination;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    /** @test */
    public function itIsAResource()
    {
        $resource = new Collection([], []);

        assertThat($resource, isInstanceOf(Resource::class));
    }

    /** @test */
    public function itProvideDataAndMeta()
    {
        $data = [['foo' => 'bar']];
        $meta = ['headers' => ['bat' => 'baz']];
        $resource = new Collection($data, $meta);

        assertThat($resource->getData(), equalTo($data));
        assertThat($resource->getMeta(), equalTo($meta));
    }

    /** @test */
    public function itProvideAPagination()
    {
        $pagination = new Pagination(1, 1, 1, 1);
        $resource = new Collection([], [], $pagination);

        assertThat($resource->getPagination(), equalTo($pagination));
    }

    /** @test */
    public function itIsTraversable()
    {
        $data = [
            ['value' => 'foo'],
            ['value' => 'bar'],
        ];

        $resource = new Collection($data, []);

        assertThat($resource, isInstanceOf(\Traversable::class));
        assertThat($resource, contains($data[0]));
        assertThat($resource, contains($data[1]));
    }
}