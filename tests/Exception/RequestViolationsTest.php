<?php
namespace ElevenLabs\Api\Service\Exception;

use PHPUnit\Framework\TestCase;

class RequestViolationsTest extends TestCase
{
    /** @test */
    public function itShouldExtendConstraintViolations()
    {
        $exception = new RequestViolations([]);

        assertThat($exception, isInstanceOf(ConstraintViolations::class));
    }
}
