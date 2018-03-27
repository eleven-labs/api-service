<?php

namespace ElevenLabs\Api\Service\Pagination\Provider;

use ElevenLabs\Api\Definition\ResponseDefinition;
use ElevenLabs\Api\Service\Pagination\Pagination;
use ElevenLabs\Api\Service\Pagination\PaginationLinks;
use ElevenLabs\Api\Service\Pagination\PaginationProvider;
use function GuzzleHttp\Psr7\parse_query;
use Psr\Http\Message\ResponseInterface;

/**
 * Class HateoasPagination
 *
 * @package App\Service\Pagination
 */
class HateoasPagination implements PaginationProvider
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
        'totalPages' => 'X-Total-Pages'
    ];

    /**
     * @var array
     */
    private $paginationHeaders;

    public function __construct(array $config = [])
    {
        foreach (self::DEFAULT_PAGINATION_HEADERS as $name => $headerName) {
            if (isset($config[$name])) {
                $headerName = $config[$name];
            }
            $this->paginationHeaders[$name] = $headerName;
        }
    }

    /**
     * @param array              $data The decoded response body
     * @param ResponseInterface  $response
     * @param ResponseDefinition $responseDefinition
     *
     * @return Pagination
     */
    public function getPagination(array &$data, ResponseInterface $response, ResponseDefinition $responseDefinition)
    {
        $paginationLinks = null;
        if (!empty($data['_links'])) {
            $links = self::parseLinks($data['_links']);
            $paginationLinks = new PaginationLinks($links['first'], $links['last'], $links['next'], $links['prev']);
        }
        $count = (int) $data[$this->paginationHeaders['totalPages']];
        $total = (int) $data[$this->paginationHeaders['totalItems']];
        $perPage = (int) ceil($count/$total);
        $currentPage = $this->getCurrentPage($data['_links']['self']['href']);
        $data = reset($data['_embedded']);

        return new Pagination($currentPage, $count, $total, $perPage, $paginationLinks);
    }

    /**
     * Indicate if the pagination is supported
     *
     * @param array $data The decoded response body
     * @param ResponseInterface $response
     * @param ResponseDefinition $responseDefinition
     *
     * @return bool
     */
    public function supportPagination(array $data, ResponseInterface $response, ResponseDefinition $responseDefinition)
    {
        if (!empty($data['_links']) && !empty($data['count']) && !empty($data['total']) && !empty($data['_embedded'])) {
            $links = ['self', 'first', 'last', 'next', 'prev'];
            foreach (array_keys($data['_links']) as $link) {
                if (!in_array($link, $links)) {
                    return false;
                } else if (empty($data['_links'][$link]['href'])) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    private static function parseLinks(array $headerLinks)
    {
        $links = ['next' => null, 'prev' => null, 'first' => null, 'last' => null];

        foreach ($headerLinks as $name => $headerLink) {
            if ($name === "self") {
                continue;
            }
            $links[$name] = $headerLink['href'];
        }

        return $links;
    }

    /**
     * @param string $self
     * @return int
     */
    private function getCurrentPage($self)
    {
        $self = parse_url($self);
        $query = parse_query($self['query']);

        return (int) $query['page'];
    }
}