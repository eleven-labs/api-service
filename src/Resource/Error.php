<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Resource;

/**
 * Class Error.
 */
class Error implements ErrorInterface
{
    private int $code;
    private string $message;
    private array $violations;

    public function __construct(int $code, string $message, array $violations)
    {
        $this->code = $code;
        $this->message = $message;
        $this->violations = $violations;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getViolations(): array
    {
        return $this->violations;
    }
}
