<?php
namespace ElevenLabs\Swagger\Http\Token;

use Prophecy\Argument\Token\TokenInterface;
use Prophecy\Util\StringUtil;
use Psr\Http\Message\RequestInterface;

/**
 * Match an expected Request against a given actual Request
 */
class MatchRequestToken implements TokenInterface
{
    private $expectedRequest;

    private $util;

    public function __construct(RequestInterface $expectedRequest)
    {
        $this->expectedRequest = $expectedRequest;
        $this->util  = new StringUtil();
    }

    public function scoreArgument($argument)
    {
        $score = 0;
        $match = (
            $argument instanceof RequestInterface &&
            $argument->getUri() == $this->expectedRequest->getUri() &&
            $argument->getHeaders() === $this->expectedRequest->getHeaders() &&
            (string) $argument->getBody() == (string) $this->expectedRequest->getBody()
        );

        if ($match === true) {
            $score = 10;
        }

        return $score;
    }

    public function isLast()
    {
        return false;
    }

    public function __toString()
    {
        $string = sprintf('match request(%s)', $this->util->stringify($this->expectedRequest));

        return $string;
    }

}
