<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Exception;

use ElevenLabs\Api\Validator\ConstraintViolation;

/**
 * Class ConstraintViolations.
 */
class ConstraintViolations extends ApiServiceError
{
    /**
     * @var array|ConstraintViolation[]
     */
    private $violations;

    /**
     * @param ConstraintViolation[] $violations
     */
    public function __construct(array $violations)
    {
        $this->violations = $violations;
        $this->message = $this->__toString();
        parent::__construct();
    }

    /**
     * @return array|ConstraintViolation[]
     */
    public function getViolations()
    {
        return $this->violations;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $message = "Request constraint violations:\n";
        foreach ($this->violations as $violation) {
            $message .= sprintf(
                "[property]: %s\n[message]: %s\n[constraint]: %s\n[location]: %s\n\n",
                $violation->getProperty(),
                $violation->getMessage(),
                $violation->getConstraint(),
                $violation->getLocation()
            );
        }

        return $message;
    }
}
