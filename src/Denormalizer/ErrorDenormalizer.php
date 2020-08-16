<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Denormalizer;

use ElevenLabs\Api\Definition\ResponseDefinition;
use ElevenLabs\Api\Service\Pagination\Provider\PaginationProviderInterface;
use ElevenLabs\Api\Service\Resource\Collection;
use ElevenLabs\Api\Service\Resource\Error;
use ElevenLabs\Api\Service\Resource\ErrorInterface;
use ElevenLabs\Api\Service\Resource\Item;
use ElevenLabs\Api\Service\Resource\ResourceInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Class ErrorDenormalizer.
 */
class ErrorDenormalizer implements DenormalizerInterface
{
    /** {@inheritdoc} */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        /** @var ResponseInterface $response */
        $response = $context['response'];

        /** @var RequestInterface $request */
        $request = $context['request'];

        /** @var ResponseDefinition $definition */
        $definition = $context['responseDefinition'];

        return new Error($response->getStatusCode(), $response->getReasonPhrase(), $data['violations'] ?? []);
    }

    /** {@inheritdoc} */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return ErrorInterface::class === $type;
    }
}
