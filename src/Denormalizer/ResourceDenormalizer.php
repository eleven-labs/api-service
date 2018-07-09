<?php
namespace ElevenLabs\Api\Service\Denormalizer;

use ElevenLabs\Api\Definition\ResponseDefinition;
use ElevenLabs\Api\Service\Pagination\PaginationProvider;
use ElevenLabs\Api\Service\Resource\Collection;
use ElevenLabs\Api\Service\Resource\Item;
use ElevenLabs\Api\Service\Resource\Resource;
use Psr\Http\Message\RequestInterface;
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

        /** @var RequestInterface $request */
        $request = $context['request'];

        /** @var ResponseDefinition $definition */
        $definition = $context['responseDefinition'];

        if (! $definition->hasBodySchema()) {
            throw new \LogicException(
                sprintf(
                    'Cannot transform the response into a resource. You need to provide a schema for response %d in %s %s',
                    $response->getStatusCode(),
                    $request->getMethod(),
                    $request->getUri()->getPath()
                )
            );
        }

        $schema = $definition->getBodySchema();
        $meta = ['headers' => $response->getHeaders()];

        if ($this->getSchemaType($schema) === 'array') {
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

    /**
     * Extract the type for a given JSON Schema
     *
     * @param \stdClass $schema
     * @throws \RuntimeException
     *
     * @return string
     */
    private function getSchemaType(\stdClass $schema)
    {
        if (isset($schema->type) === true) {
            return $schema->type;
        }
        if (isset($schema->allOf[0]->type) === true) {
            return $schema->allOf[0]->type;
        }

        throw new \RuntimeException('Cannot extract type from schema');
    }
}