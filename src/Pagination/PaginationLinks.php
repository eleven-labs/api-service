<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Pagination;

/**
 * Class PaginationLinks.
 */
class PaginationLinks
{
    private string $first;
    private string $last;
    private ?string $next;
    private ?string $prev;

    public function __construct(string $first, string $last, ?string $next = null, ?string $prev = null)
    {
        $this->first = $first;
        $this->last = $last;
        $this->next = $next;
        $this->prev = $prev;
    }

    public function getFirst(): string
    {
        return $this->first;
    }

    public function hasNext(): bool
    {
        return null !== $this->next;
    }

    public function getNext(): ?string
    {
        return $this->next;
    }

    public function hasPrev(): bool
    {
        return null !== $this->prev;
    }

    public function getPrev(): ?string
    {
        return $this->prev;
    }

    public function getLast(): string
    {
        return $this->last;
    }
}
