<?php
namespace ElevenLabs\Api\Service\Collection;

use Psr\Http\Message\ResponseInterface;

interface CollectionProvider
{
    /**
     * @param ResponseInterface $response
     * @param array $decodedContent
     *
     * @return \Traversable
     */
    public function getCollection(ResponseInterface $response, array $decodedContent);
}