<?php

declare(strict_types=1);

namespace ElevenLabs\Api\Service\Tests\Functional;

use Http\Mock\Client;
use Http\Promise\FulfilledPromise;
use Http\Promise\RejectedPromise;
use Psr\Http\Message\RequestInterface;

/**
 * Class MockClient.
 */
class MockClient extends Client
{
    /**
     * @param RequestInterface $request
     *
     * @throws \Http\Client\Exception
     *
     * @return \Http\Client\Promise\HttpFulfilledPromise|\Http\Client\Promise\HttpRejectedPromise|FulfilledPromise|RejectedPromise
     */
    public function sendAsyncRequest(RequestInterface $request)
    {
        try {
            return new FulfilledPromise($this->sendRequest($request));
        } catch (\Exception $e) {
            return new RejectedPromise($e);
        }
    }
}
