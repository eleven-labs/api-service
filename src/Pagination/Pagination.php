<?php

namespace ElevenLabs\Api\Service\Pagination;

/**
 * Class Pagination
 */
class Pagination
{
    /** @var int */
    private $page;

    /** @var int */
    private $perPage;

    /** @var int */
    private $totalItems;

    /** @var int */
    private $totalPages;

    /** @var null|PaginationLinks */
    private $links;

    /**
     * @param int                  $page
     * @param int                  $perPage
     * @param int                  $totalItems
     * @param int                  $totalPages
     * @param PaginationLinks|null $links
     */
    public function __construct($page, $perPage, $totalItems, $totalPages, $links = null)
    {
        $this->page = $page;
        $this->perPage = $perPage;
        $this->totalItems = $totalItems;
        $this->totalPages = $totalPages;
        $this->links = $links;
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
    public function getTotalItems()
    {
        return $this->totalItems;
    }

    /**
     * @return int
     */
    public function getTotalPages()
    {
        return $this->totalPages;
    }

    /**
     * @return bool
     */
    public function hasLinks()
    {
        return ($this->links !== null);
    }

    /**
     * @return PaginationLinks
     */
    public function getLinks()
    {
        return $this->links;
    }
}
