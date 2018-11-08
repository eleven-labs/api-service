<?php
namespace ElevenLabs\Api\Service;

use ElevenLabs\Api\Schema;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class ApiServiceBuilderTest extends TestCase
{
    /** @test */
    public function itShouldBuildAnApiService()
    {
        $schemaFixture = __DIR__.'/fixtures/httpbin.yml';
        $apiService = ApiServiceBuilder::create()->build('file://'.$schemaFixture);

        assertThat($apiService, isInstanceOf(ApiService::class));
    }

    /** @test */
    public function itShouldBuildAnApiServiceFromCache()
    {
        $schemaFile = 'file://fake-schema.yml';

        $schema = $this->prophesize(Schema::class);
        $schema->getSchemes()->willReturn(['https']);
        $schema->getHost()->willReturn('domain.tld');

        $item = $this->prophesize(CacheItemInterface::class);
        $item->isHit()->shouldBeCalled()->willReturn(true);
        $item->get()->shouldBeCalled()->willReturn($schema);

        $cache = $this->prophesize(CacheItemPoolInterface::class);
        $cache->getItem('3f470a326a5926a2e323aaadd767c0e64302a080')->willReturn($item);

        ApiServiceBuilder::create()
            ->withCacheProvider($cache->reveal())
            ->build($schemaFile);
    }
}
