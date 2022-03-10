<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Pagination\Provider;

use ElevenLabs\Api\Definition\ResponseDefinition;
use ElevenLabs\Api\Service\Pagination\Pagination;
use ElevenLabs\Api\Service\Pagination\PaginationLinks;
use Psr\Http\Message\ResponseInterface;

/**
 * Class HalProvider.
 */
class HalProvider implements PaginationProviderInterface
{
    const DEFAULT_PAGINATION_VALUE = [
        // current page
        'page' => '_links.self.href.page',

        // number of items per page
        'perPage' => 'itemsPerPage',

        // number of items available in total
        'totalPages' => '_links.last.href.page',

        // number of pages available
        'totalItems' => 'totalItems',
    ];

    private array $paginationName;

    public function __construct(array $config = [])
    {
        foreach (self::DEFAULT_PAGINATION_VALUE as $name => $value) {
            if (isset($config[$name])) {
                $value = $config[$name];
            }
            $this->paginationName[$name] = explode('.', $value);
        }
    }

    public function supportPagination(array $data, ResponseInterface $response, ResponseDefinition $responseDefinition): bool
    {
        $totalItems = $this->getValue($data, $this->paginationName['totalItems']);
        if (0 === $totalItems) {
            return true;
        }

        $perPage = $this->getValue($data, $this->paginationName['perPage']);
        foreach ($this->paginationName as $key => $value) {
            if ('totalPages' === $key && $totalItems <= $perPage) {
                continue;
            }

            if (null === $this->getValue($data, $value)) {
                return false;
            }
        }

        return isset($data['_embedded']['item']);
    }

    public function getPagination(array &$data, ResponseInterface $response, ResponseDefinition $responseDefinition): Pagination
    {
        $links = $data['_links'] ?? [];
        $paginationLinks = new PaginationLinks(
            $links['first']['href'] ?? $links['self']['href'] ?? '',
            $links['last']['href'] ?? $links['self']['href'] ?? '',
            $links['next']['href'] ?? null,
            $links['prev']['href'] ?? null
        );
        $pagination = [
            'page' => $this->getValue($data, $this->paginationName['page']),
            'perPage' => $this->getValue($data, $this->paginationName['perPage']),
            'totalItems' => $this->getValue($data, $this->paginationName['totalItems']),
        ];
        $data = array_map(function ($data) {
            $relations = $this->removeEmbedded($data['_embedded'] ?? []);
            unset($data['_links'], $data['_embedded']);

            foreach ($data as $key => $item) {
                $data[$key] = \is_array($item) ? $this->removeEmbedded($item) : $item;
            }

            return array_merge($relations, $data);
        }, $data['_embedded']['item'] ?? []);

        return new Pagination(
            $pagination['page'] ?? 1,
            $pagination['perPage'],
            $pagination['totalItems'],
            (int) ceil($pagination['totalItems'] / $pagination['perPage']),
            $paginationLinks
        );
    }

    private function getValue(array $items, array $values)
    {
        $value = $items;
        foreach ($values as $item) {
            if (isset($value[$item])) {
                $value = $value[$item];
            } elseif (is_string($value) && 1 === preg_match('#'.$item.'=([^&]*)#', $value, $tab)) {
                $value = isset($tab[1]) ? (int) $tab[1] : null;
            } elseif (is_string($value) && 0 === preg_match('#'.$item.'=([^&]*)#', $value)) {
                return 1;
            } else {
                return null;
            }
        }

        return $value;
    }

    private function removeEmbedded(array $items): array
    {
        return array_map(function ($item) {
            if (\is_array($item)) {
                if ($this->isArray($item)) {
                    $relations = [];
                    foreach ($item as $i) {
                        $relation = $this->removeEmbedded($i['_embedded'] ?? []);
                        unset($i['_links'], $i['_embedded']);
                        $relations[] = array_merge($relation, $i);
                    }

                    return $relations;
                } else {
                    $relations = $this->removeEmbedded($item['_embedded'] ?? []);
                    unset($item['_links'], $item['_embedded']);
                }

                return array_merge($relations, $item);
            }
            return $item;
        }, $items);
    }

    private function isArray(array $items): bool
    {
        foreach (array_keys($items) as $a) {
            if (!is_int($a)) {
                return false;
            }
        }

        return true;
    }
}
