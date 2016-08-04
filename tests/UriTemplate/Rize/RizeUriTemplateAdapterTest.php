<?php
namespace ElevenLabs\Api\Service\UriTemplate\Rize;

use PHPUnit\Framework\TestCase;

class RizeUriTemplateAdapterTest extends TestCase
{
    /** @test */
    public function itShouldExpandAnUri()
    {
        $uriTemplate = new RizeUriTemplateAdapter();
        $expanded = $uriTemplate->expand('/foo/{bar}', ['bar' => 'bar']);

        self::assertSame('/foo/bar', $expanded);
    }

    /** @test */
    public function itShouldExtractParameterFromAnUri()
    {
        $uriTemplate = new RizeUriTemplateAdapter();
        $extracted = $uriTemplate->extract('/foo/{bar}', '/foo/bar');

        self::assertSame(['bar' => 'bar'], $extracted);
    }
}
