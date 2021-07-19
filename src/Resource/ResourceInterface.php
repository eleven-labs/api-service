<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Resource;

/**
 * Interface ResourceInterface.
 */
interface ResourceInterface
{
    /**
     * Return the decoded representation of a resource
     *
     * @return array
     */
    public function getData(): array;

    /**
     * Return resource metadata such as headers
     *
     * @return array
     */
    public function getMeta(): array;
}
