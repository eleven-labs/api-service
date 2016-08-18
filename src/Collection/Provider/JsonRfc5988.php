<?php
namespace ElevenLabs\Api\Service\Collection\Provider;

use ElevenLabs\Api\Service\Collection\Provider\JsonRfc5988\LinkParser;
use ElevenLabs\Api\Service\Collection\Provider\JsonRfc5988\PagedCollection;
use ElevenLabs\Api\Service\Collection\CollectionProvider;
use ElevenLabs\Api\Service\Decoder\Decoder;
use Http\Message\UriFactory;
use Psr\Http\Message\ResponseInterface;

/**
 * Decode JSON response body and extract pagination from headers
 */
class JsonRfc5988 implements CollectionProvider
{
    /**
     * @var UriFactory
     */
    private $uriFactory;

    /**
     * @var Decoder
     */
    private $decoder;

    /**
     * @var array
     */
    private $defaultHeadersMap = [
        'page' => 'X-Pagination-Page',
        'perPage' => 'X-Pagination-Per-Page',
        'totalPages' => 'X-Pagination-Total-Pages',
        'totalItems' => 'X-Pagination-Total-Items',
    ];

    private $headersMap;

    /**
     * @param UriFactory $uriFactory
     * @param array $headersMap override default pagination metadata with custom headers
     */
    public function __construct(UriFactory $uriFactory, Decoder $decoder, array $headersMap = [])
    {
        $this->uriFactory = $uriFactory;
        $this->decoder = $decoder;
        $this->headersMap = array_merge($this->defaultHeadersMap, $headersMap);
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(ResponseInterface $response)
    {
        $paginationMeta = $this->getPaginationMetadata($response);
        $paginationLinks = $this->getPaginationLinks($response);

        return new PagedCollection(
            $this->decoder->decode($response->getBody()),
            $paginationMeta['page'],
            $paginationMeta['perPage'],
            $paginationMeta['totalPages'],
            $paginationMeta['totalItems'],
            $paginationLinks['first'],
            $paginationLinks['last'],
            $paginationLinks['next'],
            $paginationLinks['prev']
        );
    }

    public function getType()
    {
        return 'json_rfc5988';
    }

    private function getPaginationLinks(ResponseInterface $response)
    {
        $links = LinkParser::parse($response->getHeaderLine('Link'));

        return array_map(
            function ($link) {
                if ($link !== null) {
                    $link = $this->uriFactory->createUri($link);
                }
                return $link;
            },
            $links)
        ;
    }

    private function getPaginationMetadata(ResponseInterface $response)
    {
        foreach ($this->headersMap as $headerName) {
            if ($response->hasHeader($headerName) === false) {
                throw new \InvalidArgumentException($headerName.' is missing from the response object');
            }
        }

        return array_map(
            function ($headerName) use ($response) {
                return $response->getHeaderLine($headerName);
            },
            $this->headersMap
        );
    }
}