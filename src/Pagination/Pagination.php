<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Pagination;

/**
 * Class Pagination.
 */
class Pagination
{
    private int $page;
    private int $perPage;
    private int $totalItems;
    private int $totalPages;
    private ?PaginationLinks $links;

    public function __construct(int $page, int $perPage, int $totalItems, int $totalPages, ?PaginationLinks $links = null)
    {
        $this->page = $page;
        $this->perPage = $perPage;
        $this->totalItems = $totalItems;
        $this->totalPages = $totalPages;
        $this->links = $links;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    public function hasLinks(): bool
    {
        return null !== $this->links;
    }

    public function getLinks(): ?PaginationLinks
    {
        return $this->links;
    }
}
