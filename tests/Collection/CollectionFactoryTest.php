<?php
namespace ElevenLabs\Api\Service\Collection;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class CollectionFactoryTest extends TestCase
{
    /** @test */
    public function itResolveACollectionProviderFromAGivenType()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $collectionProvider = $this->prophesize(CollectionProvider::class);
        $collectionProvider->getType()->willReturn('mocked_provider');
        $collectionProvider->getCollection($response)->shouldBeCalled()->willReturn(new \ArrayIterator([]));

        $factory = new CollectionFactory([$collectionProvider->reveal()]);

        $actual = $factory->createCollection($response->reveal(), 'mocked_provider');

        self::assertInstanceOf(\Traversable::class, $actual);
    }
}