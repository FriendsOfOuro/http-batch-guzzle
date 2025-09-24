<?php

declare(strict_types=1);

namespace FriendsOfOuro\Http\Batch\Guzzle\Exception;

use GuzzleHttp\Exception\RequestException as RequestExceptionAlias;
use Psr\Http\Message\ResponseInterface;

class ClientException extends RequestException
{
    public function getResponse(): ?ResponseInterface
    {
        $previous = $this->getPrevious();
        assert($previous instanceof RequestExceptionAlias);

        return $previous->getResponse();
    }
}
