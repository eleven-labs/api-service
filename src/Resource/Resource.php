<?php

namespace ElevenLabs\Api\Service\Resource;

/**
 * Interface Resource.
 */
interface Resource
{
    /**
     * Return the decoded representation of a resource
     *
     * @return array
     */
    public function getData();

    /**
     * Return resource metadata such as headers
     *
     * @return array
     */
    public function getMeta();
}
