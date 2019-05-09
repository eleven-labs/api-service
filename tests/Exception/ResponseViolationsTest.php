<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Exception\Tests;

use ElevenLabs\Api\Service\Exception\ConstraintViolations;
use ElevenLabs\Api\Service\Exception\ResponseViolations;
use PHPUnit\Framework\TestCase;

/**
 * Class ResponseViolationsTest.
 */
class ResponseViolationsTest extends TestCase
{
    /** @test */
    public function itShouldExtendConstraintViolations()
    {
        $exception = new ResponseViolations([]);

        $this->assertInstanceOf(ConstraintViolations::class, $exception);
    }
}
