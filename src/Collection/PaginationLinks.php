<?php
namespace ElevenLabs\Api\Service\Collection;

use Psr\Http\Message\UriInterface;

interface PaginationLinks
{
    /**
     * @return UriInterface
     */
    public function getNext();

    /**
     * @return UriInterface
     */
    public function getPrev();

    /**
     * @return UriInterface
     */
    public function getFirst();

    /**
     * @return UriInterface
     */
    public function getLast();

    /**
     * @return bool
     */
    public function hasNext();

    /**
     * @return bool
     */
    public function hasPrev();
}
