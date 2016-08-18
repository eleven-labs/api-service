<?php
namespace ElevenLabs\Api\Service\Collection;


use Psr\Http\Message\ResponseInterface;

class CollectionFactory
{
    /**
     * @var CollectionProvider[]
     */
    private $providers;

    public function __construct(array $providers = [])
    {
        foreach ($providers as $provider) {
            $this->addProvider($provider);
        }
    }

    public function addProvider(CollectionProvider $provider)
    {
        $this->providers[$provider->getType()] = $provider;
    }

    /**
     * @param ResponseInterface $response
     * @param string $type
     *
     * @return Collection
     */
    public function createCollection(ResponseInterface $response, $type)
    {
        return $this->getProvider($type)->getCollection($response);
    }

    /**
     * @param string $type
     *
     * @return CollectionProvider
     */
    private function getProvider($type)
    {
        if (! isset($this->providers[$type])) {
            throw new \RuntimeException('Unable to find a collection provider for type ' . $type);
        }

        return $this->providers[$type];
    }
}