<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Tests\Exception;

use ElevenLabs\Api\Service\Exception\ConstraintViolations;
use ElevenLabs\Api\Service\Exception\RequestViolations;
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

        $this->assertInstanceOf(ConstraintViolations::class, $exception);
    }
}
