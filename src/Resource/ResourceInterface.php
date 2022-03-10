<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Resource;

/**
 * Interface ResourceInterface.
 */
interface ResourceInterface
{
    public function getData(): array;
    public function getMeta(): array;
}
