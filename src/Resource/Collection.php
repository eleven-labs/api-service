<?php
namespace ElevenLabs\Api\Service\Resource;

use ElevenLabs\Api\Service\Pagination\Pagination;

class Collection extends Item implements \IteratorAggregate
{
    /**
     * @var Pagination|null
     */
    protected $pagination;

    /**
     * @param array $data
     * @param array $meta
     * @param Pagination|null $pagination
     */
    public function __construct(array $data, array $meta, Pagination $pagination = null)
    {
        parent::__construct($data, $meta);
        $this->pagination = $pagination;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->getData());
    }

    public function hasPagination()
    {
        return ($this->pagination !== null);
    }

    public function getPagination()
    {
        return $this->pagination;
    }
}
