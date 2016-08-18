<?php
namespace ElevenLabs\Api\Service\Collection\Provider;

use ElevenLabs\Api\Service\Collection\CollectionProvider;
use Psr\Http\Message\ResponseInterface;

class JsonDefault implements CollectionProvider
{
    public function getCollection(ResponseInterface $response)
    {
        return new \ArrayIterator(json_decode($response, true));
    }

    public function getType()
    {
        return 'json';
    }
}