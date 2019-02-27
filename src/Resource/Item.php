<?php

namespace ElevenLabs\Api\Service\Resource;

/**
 * Class Item.
 */
class Item implements Resource
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var array
     */
    private $meta;

    /**
     * Item constructor.
     *
     * @param array $data
     * @param array $meta
     */
    public function __construct(array $data, array $meta)
    {
        $this->data = $data;
        $this->meta = $meta;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getMeta()
    {
        return $this->meta;
    }
}
