<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Pagination\Provider;

use ElevenLabs\Api\Definition\ResponseDefinition;
use ElevenLabs\Api\Service\Pagination\Pagination;
use Psr\Http\Message\ResponseInterface;

/**
 * Interface PaginationProviderInterface.
 */
interface PaginationProviderInterface
{
    /**
     * @param array              $data
     * @param ResponseInterface  $response
     * @param ResponseDefinition $responseDefinition
     *
     * @return bool
     */
    public function supportPagination(array $data, ResponseInterface $response, ResponseDefinition $responseDefinition): bool;

    /**
     * @param array              $data
     * @param ResponseInterface  $response
     * @param ResponseDefinition $responseDefinition
     *
     * @return Pagination
     */
    public function getPagination(array &$data, ResponseInterface $response, ResponseDefinition $responseDefinition): Pagination;
}
