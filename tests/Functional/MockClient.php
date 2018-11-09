<?php

namespace ElevenLabs\Api\Service\Functional;

use Http\Mock\Client;
use Http\Promise\FulfilledPromise;
use Http\Promise\RejectedPromise;
use Psr\Http\Message\RequestInterface;

class MockClient extends Client
{
    public function sendAsyncRequest(RequestInterface $request)
    {
        try {
            return new FulfilledPromise($this->sendRequest($request));
        } catch (\Exception $e) {
            return new RejectedPromise($e);
        }
    }
}
