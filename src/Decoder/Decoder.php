<?php
namespace ElevenLabs\Api\Service\Decoder;

use Psr\Http\Message\StreamInterface;

interface Decoder
{
    public function decode(StreamInterface $stream);
}