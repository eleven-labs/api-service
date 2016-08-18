<?php
namespace ElevenLabs\Api\Service\Collection\Provider\HeaderCollection;

use Psr\Http\Message\UriInterface;
use ElevenLabs\Api\Service\Collection\PaginationLinks as PaginationLinksInterface;

class PaginationLinks implements PaginationLinksInterface
{
    /** @var UriInterface */
    private $first;
    /** @var UriInterface */
    private $last;
    /** @var UriInterface */
    private $next;
    /** @var UriInterface */
    private $prev;

    /**
     * @param UriInterface $first
     * @param UriInterface $last
     * @param UriInterface|null $next
     * @param UriInterface|null $prev
     */
    public function __construct(UriInterface $first, UriInterface $last, UriInterface $next = null, UriInterface $prev = null)
    {
        $this->first = $first;
        $this->last = $last;
        $this->next = $next;
        $this->prev = $prev;
    }

    /**
     * @return UriInterface
     */
    public function getFirst()
    {
        return $this->first;
    }

    /**
     * @return UriInterface
     */
    public function getLast()
    {
        return $this->last;
    }

    /**
     * @return UriInterface
     */
    public function getNext()
    {
        return $this->next;
    }

    /**
     * @return UriInterface
     */
    public function getPrev()
    {
        return $this->prev;
    }

    public function hasNext()
    {
        return $this->next !== null;
    }

    public function hasPrev()
    {
        return $this->prev !== null;
    }
}
