<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Denormalizer;

use ElevenLabs\Api\Definition\ResponseDefinition;
use ElevenLabs\Api\Service\Pagination\Provider\PaginationProviderInterface;
use ElevenLabs\Api\Service\Resource\Collection;
use ElevenLabs\Api\Service\Resource\Item;
use ElevenLabs\Api\Service\Resource\ResourceInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Class ResourceDenormalizer.
 */
class ResourceDenormalizer implements DenormalizerInterface
{
    private ?PaginationProviderInterface $paginationProvider;

    public function __construct($paginationProvider = null)
    {
        $this->paginationProvider = '' === $paginationProvider ? null : $paginationProvider;
    }

    public function denormalize($data, $type, $format = null, array $context = array())
    {
        /** @var ResponseInterface $response */
        $response = $context['response'];

        /** @var RequestInterface $request */
        $request = $context['request'];

        /** @var ResponseDefinition $definition */
        $definition = $context['responseDefinition'];

        if (!$definition->hasBodySchema()) {
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

        if ('array' === $this->getSchemaType($schema)) {
            $pagination = null;
            if (null !== $this->paginationProvider &&
                $this->paginationProvider->supportPagination($data, $response, $definition)
            ) {
                $pagination = $this->paginationProvider->getPagination($data, $response, $definition);
            }

            return new Collection($data, $meta, $pagination);
        }

        return new Item($data, $meta);
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return ResourceInterface::class === $type;
    }

    private function getSchemaType(\stdClass $schema): string
    {
        if (true === isset($schema->type)) {
            return $schema->type;
        }

        if (true === isset($schema->allOf[0]->type)) {
            return $schema->allOf[0]->type;
        }

        throw new \RuntimeException('Cannot extract type from schema');
    }
}
