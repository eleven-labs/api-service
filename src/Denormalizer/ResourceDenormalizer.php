<?php
namespace ElevenLabs\Api\Service\Denormalizer;

use ElevenLabs\Api\Definition\ResponseDefinition;
use ElevenLabs\Api\Service\Pagination\PaginationProvider;
use ElevenLabs\Api\Service\Resource\Collection;
use ElevenLabs\Api\Service\Resource\Item;
use ElevenLabs\Api\Service\Resource\Resource;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ResourceDenormalizer implements DenormalizerInterface
{
    private $paginationProvider;

    public function __construct(PaginationProvider $paginationProvider = null)
    {
        $this->paginationProvider = $paginationProvider;
    }

    /** {@inheritdoc} */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        /** @var ResponseInterface $response */
        $response = $context['response'];

        /** @var ResponseDefinition $definition */
        $definition = $context['definition'];

        $meta = ['headers' => $response->getHeaders()];
        $schema = $definition->getSchema();

        if ($schema->type === 'array') {
            $pagination = null;
            if ($this->paginationProvider !== null &&
                $this->paginationProvider->supportPagination($data, $response, $definition)
            ) {
                $pagination = $this->paginationProvider->getPagination($data, $response, $definition);
            }

            return new Collection($data, $meta, $pagination);
        }

        return new Item($data, $meta);
    }

    /** {@inheritdoc} */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return ($type === Resource::class);
    }
}