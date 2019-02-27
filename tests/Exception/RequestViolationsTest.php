<?php

namespace ElevenLabs\Api\Service\Exception;

use PHPUnit\Framework\TestCase;

/**
 * Class RequestViolationsTest.
 */
class RequestViolationsTest extends TestCase
{
    /** @test */
    public function itShouldExtendConstraintViolations()
    {
        $exception = new RequestViolations([]);

        assertThat($exception, isInstanceOf(ConstraintViolations::class));
    }
}
