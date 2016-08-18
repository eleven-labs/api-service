<?php
namespace ElevenLabs\Api\Service\Collection\Provider\HeaderCollection;

use Countable;
use ElevenLabs\Api\Service\Collection\Pagination;
use IteratorAggregate;

class PagedCollection implements IteratorAggregate, Countable, Pagination
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
    private $totalEntries;

    /** @var PaginationLinks */
    private $links;

    /**
     * @param array $items
     * @param int $page
     * @param int $perPage
     * @param int $totalPages
     * @param int $totalEntries
     * @param PaginationLinks|null $links
     */
    public function __construct(
        array $items,
        $page,
        $perPage,
        $totalPages,
        $totalEntries,
        $links = null
    ) {
        $this->items = $items;
        $this->page = $page;
        $this->perPage = $perPage;
        $this->totalPages = $totalPages;
        $this->totalEntries = $totalEntries;
        $this->links = $links;
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
        return $this->getTotalEntries();
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
    public function getTotalEntries()
    {
        return $this->totalEntries;
    }

    /**
     * @return bool
     */
    public function hasLinks()
    {
        return $this->links !== null;
    }

    /**
     * @return PaginationLinks
     */
    public function getLinks()
    {
        return $this->links;
    }

}