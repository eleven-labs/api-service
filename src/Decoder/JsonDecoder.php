<?php
namespace ElevenLabs\Api\Service\Decoder;

use Psr\Http\Message\StreamInterface;

class JsonDecoder implements Decoder
{
    public function decode(StreamInterface $stream)
    {
        return json_decode((string) $stream, true);
    }
}