<?php
namespace ElevenLabs\Api\Service\Collection\Provider\JsonRfc5988;

use ElevenLabs\Api\Service\Collection\Pageable;
use Psr\Http\Message\UriInterface;

class PagedCollection implements \IteratorAggregate, \Countable, Pageable
{
    /** @var array */
    private $items;

    /** @var int */
    private $page;

    /** @var int */
    private $perPage;

    /** @var int */
    private $totalPages;

    /** @var int */
    private $totalItems;

    /** @var UriInterface|null */
    private $next;

    /** @var UriInterface|null */
    private $prev;

    /** @var UriInterface */
    private $first;

    /** @var UriInterface */
    private $last;

    /**
     * @param array $items
     * @param $page
     * @param $perPage
     * @param $totalPages
     * @param $totalItems
     * @param UriInterface $first
     * @param UriInterface $last
     * @param UriInterface|null $next
     * @param UriInterface|null $prev
     */
    public function __construct(
        array $items,
        $page,
        $perPage,
        $totalPages,
        $totalItems,
        UriInterface $first,
        UriInterface $last,
        UriInterface $next = null,
        UriInterface $prev = null
    ) {
        $this->items = $items;
        $this->page = $page;
        $this->perPage = $perPage;
        $this->totalPages = $totalPages;
        $this->totalItems = $totalItems;
        $this->next = $next;
        $this->prev = $prev;
        $this->first = $first;
        $this->last = $last;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->getTotalItems();
    }

    /**
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @return int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * @return int
     */
    public function getTotalPages()
    {
        return $this->totalPages;
    }

    /**
     * @return int
     */
    public function getTotalItems()
    {
        return $this->totalItems;
    }

    /**
     * @return null|UriInterface
     */
    public function getNext()
    {
        return $this->next;
    }

    /**
     * @return null|UriInterface
     */
    public function getPrev()
    {
        return $this->prev;
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
     * {@inheritdoc}
     */
    public function hasNext()
    {
        return $this->next !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function hasPrev()
    {
        return $this->prev !== null;
    }

}