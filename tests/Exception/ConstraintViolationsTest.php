<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Exception\Tests;

use ElevenLabs\Api\Service\Exception\ApiServiceError;
use ElevenLabs\Api\Service\Exception\ConstraintViolations;
use ElevenLabs\Api\Validator\ConstraintViolation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class ConstraintViolationsTest.
 */
class ConstraintViolationsTest extends TestCase
{
    /** @test */
    public function itShouldExtendApiServiceError()
    {
        $exception = new ConstraintViolations([]);

        $this->assertInstanceOf(ApiServiceError::class, $exception);
    }

    /** @test */
    public function itShouldProvideTheListOfViolations()
    {
        /** @var ConstraintViolation|MockObject $violation */
        $violation = $this->createMock(ConstraintViolation::class);
        $violation->expects($this->once())->method('getProperty')->willReturn('foo');
        $violation->expects($this->once())->method('getMessage')->willReturn('bar is not a string');
        $violation->expects($this->once())->method('getConstraint')->willReturn('');
        $violation->expects($this->once())->method('getLocation')->willReturn('foo');

        $exception = new ConstraintViolations([$violation]);

        $this->assertSame([$violation], $exception->getViolations());
    }
}
