<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Resource;

use ElevenLabs\Api\Service\Pagination\Pagination;

/**
 * Class Collection.
 */
class Collection extends Item implements \IteratorAggregate
{
    protected ?Pagination $pagination;

    public function __construct(array $data, array $meta, ?Pagination $pagination = null)
    {
        parent::__construct($data, $meta);
        $this->pagination = $pagination;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->getData());
    }

    public function hasPagination(): bool
    {
        return null !== $this->pagination;
    }

    public function getPagination(): ?Pagination
    {
        return $this->pagination;
    }
}
