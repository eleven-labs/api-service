<?php
namespace ElevenLabs\Swagger\Http\UriTemplate;

/**
 * URI Template Interface
 */
interface UriTemplate
{
    /**
     * Expands URI Template
     *
     * @param string $uri  URI Template
     * @param array  $params        URI Template's parameters
     * @return string
     */
    public function expand($uri, array $params = []);

    /**
     * Extracts variables from URI
     *
     * @param  string $template
     * @param  string $uri
     * @param  bool   $strict  This will perform a full match
     * @return null|array params or null if not match and $strict is true
     */
    public function extract($template, $uri, $strict = false);
}