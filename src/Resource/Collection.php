<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Resource;

use ElevenLabs\Api\Service\Pagination\Pagination;

/**
 * Class Collection.
 */
class Collection extends Item implements \IteratorAggregate
{
    /**
     * @var Pagination|null
     */
    protected $pagination;

    /**
     * @param array           $data
     * @param array           $meta
     * @param array           $body
     * @param Pagination|null $pagination
     */
    public function __construct(array $data, array $meta, array $body, ?Pagination $pagination = null)
    {
        parent::__construct($data, $meta, $body);
        $this->pagination = $pagination;
    }

    /**
     * @return \ArrayIterator|\Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->getData());
    }

    /**
     * @return bool
     */
    public function hasPagination(): bool
    {
        return null !== $this->pagination;
    }

    /**
     * @return Pagination|null
     */
    public function getPagination(): ?Pagination
    {
        return $this->pagination;
    }
}
