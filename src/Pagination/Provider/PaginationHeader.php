<?php

namespace ElevenLabs\Api\Service\Pagination\Provider;

use ElevenLabs\Api\Definition\ResponseDefinition;
use ElevenLabs\Api\Service\Pagination\Pagination;
use ElevenLabs\Api\Service\Pagination\PaginationLinks;
use ElevenLabs\Api\Service\Pagination\PaginationProvider;
use Psr\Http\Message\ResponseInterface;

/**
 * Class PaginationHeader.
 */
class PaginationHeader implements PaginationProvider
{
    /**
     * Default mapping for pagination request headers
     *
     * @var array
     */
    const DEFAULT_PAGINATION_HEADERS = [
        // current page
        'page' => 'X-Page',
        // number of items per page
        'perPage' => 'X-Per-Page',
        // number of items available in total
        'totalItems' => 'X-Total-Items',
        // number of pages available
        'totalPages' => 'X-Total-Pages',
    ];

    /**
     * @var array
     */
    private $paginationHeaders;

    /**
     * PaginationHeader constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        foreach (self::DEFAULT_PAGINATION_HEADERS as $name => $headerName) {
            if (isset($config[$name])) {
                $headerName = $config[$name];
            }
            $this->paginationHeaders[$name] = $headerName;
        }
    }

    /** {@inheritdoc} */
    public function getPagination(array &$data, ResponseInterface $response, ResponseDefinition $responseDefinition)
    {
        $paginationLinks = null;
        if ($response->hasHeader('Link')) {
            $links = self::parseHeaderLinks($response->getHeader('Link'));
            $paginationLinks = new PaginationLinks(
                $links['first'],
                $links['last'],
                $links['next'],
                $links['prev']
            );
        }

        return new Pagination(
            (int) $response->getHeaderLine($this->paginationHeaders['page']),
            (int) $response->getHeaderLine($this->paginationHeaders['perPage']),
            (int) $response->getHeaderLine($this->paginationHeaders['totalItems']),
            (int) $response->getHeaderLine($this->paginationHeaders['totalPages']),
            $paginationLinks
        );
    }

    /** {@inheritdoc} */
    public function supportPagination(array $data, ResponseInterface $response, ResponseDefinition $responseDefinition)
    {
        $support = true;
        foreach ($this->paginationHeaders as $headerName) {
            $support = $support & ($response->getHeaderLine($headerName) !== '');
        }

        return (bool) $support;
    }

    /**
     * @param array $headerLinks
     *
     * @return array
     */
    private static function parseHeaderLinks(array $headerLinks)
    {
        $links = ['next' => null, 'prev' => null];

        foreach ($headerLinks as $headerLink) {
            preg_match('/rel="([^"]+)"/', $headerLink, $matches);
            if (\count($matches) == 2 && in_array($matches[1], ['next', 'prev', 'first', 'last'])) {
                $parts = explode(';', $headerLink);
                $url = trim($parts[0], " <>");
                $links[$matches[1]] = $url;
            }
        }

        return $links;
    }
}
