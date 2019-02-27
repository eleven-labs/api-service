<?php

namespace ElevenLabs\Api\Service\Pagination;

use ElevenLabs\Api\Definition\ResponseDefinition;
use ElevenLabs\Api\Service\Pagination\Pagination;
use Psr\Http\Message\ResponseInterface;

/**
 * Interface PaginationProvider.
 */
interface PaginationProvider
{
    /**
     * @param array              $data               The decoded response body
     * @param ResponseInterface  $response
     * @param ResponseDefinition $responseDefinition
     *
     * @return Pagination
     */
    public function getPagination(array &$data, ResponseInterface $response, ResponseDefinition $responseDefinition);

    /**
     * Indicate if the pagination is supported
     *
     * @param array              $data               The decoded response body
     * @param ResponseInterface  $response
     * @param ResponseDefinition $responseDefinition
     *
     * @return bool
     */
    public function supportPagination(array $data, ResponseInterface $response, ResponseDefinition $responseDefinition);
}
