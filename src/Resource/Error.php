<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Resource;

/**
 * Class Error.
 */
class Error implements ErrorInterface
{
    /**
     * @var int
     */
    private $code;

    /**
     * @var string
     */
    private $message;

    /**
     * @var array
     */
    private $violations;

    public function __construct(int $code, string $message, array $violations)
    {
        $this->code = $code;
        $this->message = $message;
        $this->violations = $violations;
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return array
     */
    public function getViolations(): array
    {
        return $this->violations;
    }
}
