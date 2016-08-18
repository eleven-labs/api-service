<?php
namespace ElevenLabs\Api\Service\Collection;

use Psr\Http\Message\ResponseInterface;

interface CollectionProvider
{
    /**
     * @param ResponseInterface $response
     *
     * @return \Traversable
     */
    public function getCollection(ResponseInterface $response);

    /**
     * @return string
     */
    public function getType();
}