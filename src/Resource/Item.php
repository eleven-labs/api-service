<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Resource;

/**
 * Class Item.
 */
class Item implements ResourceInterface
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
     * @var array
     */
    private $body;

    /**
     * Item constructor.
     *
     * @param array $data
     * @param array $meta
     * @param array $body
     */
    public function __construct(array $data, array $meta, array $body)
    {
        $this->data = $data;
        $this->meta = $meta;
        $this->body = $body;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * @return array
     */
    public function getBody(): array
    {
        return $this->body;
    }
}
