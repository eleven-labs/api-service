<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Pagination;

/**
 * Class Pagination.
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

    /** @var PaginationLinks|null */
    private $links;

    /**
     * @param int                  $page
     * @param int                  $perPage
     * @param int                  $totalItems
     * @param int                  $totalPages
     * @param PaginationLinks|null $links
     */
    public function __construct(int $page, int $perPage, int $totalItems, int $totalPages, ?PaginationLinks $links = null)
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
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @return int
     */
    public function getPerPage(): int
    {
        return $this->perPage;
    }

    /**
     * @return int
     */
    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    /**
     * @return int
     */
    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    /**
     * @return bool
     */
    public function hasLinks(): bool
    {
        return null !== $this->links;
    }

    /**
     * @return PaginationLinks|null
     */
    public function getLinks(): ?PaginationLinks
    {
        return $this->links;
    }
}
