<?php

namespace ElevenLabs\Api\Service\Pagination\Provider;

use ElevenLabs\Api\Definition\ResponseDefinition;
use ElevenLabs\Api\Service\Pagination\Pagination;
use ElevenLabs\Api\Service\Pagination\PaginationLinks;
use ElevenLabs\Api\Service\Pagination\PaginationProvider;
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
    const DEFAULT_PAGINATION_VALUE = [
        // current page
        'page' => 'page',
        // number of items per page
        'perPage' => 'perPage',
        // number of items available in total
        'totalItems' => 'totalItems',
        // number of pages available
        'totalPages' => 'totalPages'
    ];

    /**
     * @var array
     */
    private $paginationHeaders;

    public function __construct(array $config = [])
    {
        foreach (self::DEFAULT_PAGINATION_VALUE as $name => $headerName) {
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
        $perPage = (int) $data[$this->paginationHeaders['perPage']];
        $currentPage = (int) $data[$this->paginationHeaders['page']];
        $data = reset($data['_embedded']);

        return new Pagination($currentPage, $perPage, $total, $count, $paginationLinks);
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
        foreach ($this->paginationHeaders as $value) {
            if (!isset($data[$value])) {
                return false;
            }
        }

        if (isset($data['_links']) && isset($data['_embedded'])) {
            $links = $data['_links'];
            if (isset($links['self']) && isset($links['first']) && isset($links['last'])) {
                return true;
            }
            return false;
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
}
