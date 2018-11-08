<?php
namespace ElevenLabs\Api\Service\Exception;

use ElevenLabs\Api\Validator\ConstraintViolation;
use PHPUnit\Framework\TestCase;

class ConstraintViolationsTest extends TestCase
{
    /** @test */
    public function itShouldExtendApiServiceError()
    {
        $exception = new ConstraintViolations([]);

        assertThat($exception, isInstanceOf(ApiServiceError::class));
    }

    /** @test */
    public function itShouldProvideTheListOfViolations()
    {
        $violationList = [$this->prophesize(ConstraintViolation::class)->reveal()];

        $exception = new ConstraintViolations($violationList);

        assertThat($exception->getViolations(), equalTo($violationList));
    }
}
