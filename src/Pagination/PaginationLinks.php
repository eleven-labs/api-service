<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Pagination;

/**
 * Class PaginationLinks.
 */
class PaginationLinks
{
    /**
     * @var string
     */
    private $first;

    /**
     * @var string
     */
    private $last;

    /**
     * @var string|null
     */
    private $next;

    /**
     * @var string|null
     */
    private $prev;

    /**
     * PaginationLinks constructor.
     *
     * @param string      $first
     * @param string      $last
     * @param string|null $next
     * @param string|null $prev
     */
    public function __construct(string $first, string $last, ?string $next = null, ?string $prev = null)
    {
        $this->first = $first;
        $this->last = $last;
        $this->next = $next;
        $this->prev = $prev;
    }

    /**
     * @return string
     */
    public function getFirst(): string
    {
        return $this->first;
    }

    /**
     * @return bool
     */
    public function hasNext(): bool
    {
        return null !== $this->next;
    }

    /**
     * @return string
     */
    public function getNext(): ?string
    {
        return $this->next;
    }

    /**
     * @return bool
     */
    public function hasPrev(): bool
    {
        return null !== $this->prev;
    }

    /**
     * @return string
     */
    public function getPrev(): ?string
    {
        return $this->prev;
    }

    /**
     * @return string
     */
    public function getLast(): string
    {
        return $this->last;
    }
}
