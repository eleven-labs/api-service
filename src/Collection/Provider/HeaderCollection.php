<?php
namespace ElevenLabs\Api\Service\Collection\Provider;

use ElevenLabs\Api\Service\Collection\Provider\HeaderCollection\LinkParser;
use ElevenLabs\Api\Service\Collection\Provider\HeaderCollection\PagedCollection;
use ElevenLabs\Api\Service\Collection\CollectionProvider;
use ElevenLabs\Api\Service\Collection\Provider\HeaderCollection\PaginationLinks;
use Http\Message\UriFactory;
use Psr\Http\Message\ResponseInterface;

class HeaderCollection implements CollectionProvider
{
    /**
     * @var UriFactory
     */
    private $uriFactory;

    /**
     * @var array
     */
    private $defaultOptions = [
        'supportLink' => true,
        'page' => 'X-Pagination-Page',
        'perPage' => 'X-Pagination-Per-Page',
        'totalPages' => 'X-Pagination-Total-Pages',
        'totalEntries' => 'X-Pagination-Total-Entries',
    ];

    /**
     * @var array
     */
    private $metadataHeaders;

    private $supportLink;

    /**
     * @param UriFactory $uriFactory
     * @param array $options override default pagination metadata with custom headers
     */
    public function __construct(UriFactory $uriFactory, array $options = [])
    {
        $this->uriFactory = $uriFactory;

        $options = array_merge($this->defaultOptions, $options);

        $this->metadataHeaders = [
            'page' => $options['page'],
            'perPage' => $options['perPage'],
            'totalPages' => $options['totalPages'],
            'totalEntries' => $options['totalEntries']
        ];

        $this->supportLink = $options['supportLink'];
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(ResponseInterface $response, array $decodedContent)
    {
        $paginationMeta = $this->getPaginationMetadata($response);
        $paginationLinks = $this->getPaginationLinks($response);

        return new PagedCollection(
            $decodedContent,
            $paginationMeta['page'],
            $paginationMeta['perPage'],
            $paginationMeta['totalPages'],
            $paginationMeta['totalEntries'],
            $paginationLinks
        );
    }

    /**
     * @param ResponseInterface $response
     *
     * @return PaginationLinks|null
     */
    private function getPaginationLinks(ResponseInterface $response)
    {
        if ($this->supportLink === false) {
            return null;
        }

        $links = array_map(
            function ($link) {
                if ($link !== null) {
                    $link = $this->uriFactory->createUri($link);
                }

                return $link;
            },
            LinkParser::parse($response->getHeaderLine('Link'))
        );

        return new PaginationLinks(
            $links['first'],
            $links['last'],
            $links['next'],
            $links['prev']
        );
    }

    private function getPaginationMetadata(ResponseInterface $response)
    {
        foreach ($this->metadataHeaders as $headerName) {
            if ($response->hasHeader($headerName) === false) {
                throw new \InvalidArgumentException($headerName.' is missing from the response object');
            }
        }

        return array_map(
            function ($headerName) use ($response) {
                $value = $response->getHeaderLine($headerName);
                if (! ctype_digit($value)) {
                    throw new \LogicException(sprintf('The value of the header "%s" should be an integer', $headerName));
                }

                return (int) $value;
            },
            $this->metadataHeaders
        );
    }
}