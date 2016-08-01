<?php
namespace ElevenLabs\Swagger\Http\UriTemplate\Rize;

use Rize\UriTemplate as RizeUriTemplate;
use ElevenLabs\Swagger\Http\UriTemplate\UriTemplate as UriTemplateInterface;

class UriTemplate implements UriTemplateInterface
{
    private $uriTemplate;

    public function __construct(RizeUriTemplate $uriTemplate = null)
    {
        if ($uriTemplate === null) {
            $uriTemplate = new RizeUriTemplate;
        }
        $this->uriTemplate = $uriTemplate;
    }

    public function expand($uri, array $params = [])
    {
        return $this->uriTemplate->expand($uri, $params);
    }

    public function extract($template, $uri, $strict = false)
    {
        return $this->uriTemplate->extract($template, $uri, $strict);
    }

}