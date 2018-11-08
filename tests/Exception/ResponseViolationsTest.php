<?php
namespace ElevenLabs\Api\Service\Exception;

use PHPUnit\Framework\TestCase;

class ResponseViolationsTest extends TestCase
{
    /** @test */
    public function itShouldExtendConstraintViolations()
    {
        $exception = new ResponseViolations([]);

        assertThat($exception, isInstanceOf(ConstraintViolations::class));
    }
}
