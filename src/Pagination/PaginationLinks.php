<?php
namespace ElevenLabs\Api\Service\Pagination;

class PaginationLinks
{
    /** @var string */
    private $first;
    /** @var string */
    private $last;
    /** @var string */
    private $next;
    /** @var string */
    private $prev;

    /**
     * PaginationLinks constructor.
     * @param string $first
     * @param string $last
     * @param string $next
     * @param string $prev
     */
    public function __construct($first, $last, $next = null, $prev = null)
    {
        $this->first = $first;
        $this->last = $last;
        $this->next = $next;
        $this->prev = $prev;
    }

    /**
     * @return string
     */
    public function getFirst()
    {
        return $this->first;
    }

    public function hasLast()
    {
        return ($this->last !== null);
    }

    public function hasNext()
    {
        return ($this->last !== null);
    }

    /**
     * @return string
     */
    public function getNext()
    {
        return $this->next;
    }

    public function hasPrev()
    {
        return ($this->last !== null);
    }

    /**
     * @return string
     */
    public function getPrev()
    {
        return $this->prev;
    }
}