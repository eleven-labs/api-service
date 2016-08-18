<?php
namespace ElevenLabs\Api\Service\Collection\Provider\HeaderCollection;

class LinkParser
{
    public static function parse($linkHeader)
    {
        $pagination = [
            'next' => null,
            'prev' => null,
        ];
        $links = explode(',', $linkHeader);

        foreach ($links as $link) {
            preg_match('/rel="([^"]+)"/', $link, $matches);

            if (isset($matches[1]) && in_array($matches[1], ['next', 'prev', 'first', 'last'])) {
                // extract url
                $parts = explode(';', $link);
                $url = trim($parts[0], " <>");

                $pagination[$matches[1]] = $url;
            }
        }

        return $pagination;
    }
}