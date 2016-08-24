<?php
namespace ElevenLabs\Api\Service\Resource;

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

    public function __construct(array $data, array $meta)
    {
        $this->data = $data;
        $this->meta = $meta;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getMeta()
    {
        return $this->meta;
    }
}