<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Pagination\Provider;

use ElevenLabs\Api\Definition\ResponseDefinition;
use ElevenLabs\Api\Service\Pagination\Pagination;
use ElevenLabs\Api\Service\Pagination\PaginationLinks;
use Psr\Http\Message\ResponseInterface;

/**
 * Class HateoasPagination.
 */
class HateoasPagination implements PaginationProviderInterface
{
    /**
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
        'totalPages' => 'totalPages',
    ];

    /**
     * @var array
     */
    private $paginationHeaders;

    /**
     * @param array $config
     */
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
     * @param array              $data
     * @param ResponseInterface  $response
     * @param ResponseDefinition $responseDefinition
     *
     * @return Pagination
     */
    public function getPagination(array &$data, ResponseInterface $response, ResponseDefinition $responseDefinition): Pagination
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
     * @param array              $data
     * @param ResponseInterface  $response
     * @param ResponseDefinition $responseDefinition
     *
     * @return bool
     */
    public function supportPagination(array $data, ResponseInterface $response, ResponseDefinition $responseDefinition): bool
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

    /**
     * @param array $headerLinks
     *
     * @return array
     */
    private static function parseLinks(array $headerLinks): array
    {
        $links = ['next' => null, 'prev' => null, 'first' => null, 'last' => null];

        foreach ($headerLinks as $name => $headerLink) {
            if ('self' === $name) {
                continue;
            }

            $links[$name] = $headerLink['href'];
        }

        return $links;
    }
}
