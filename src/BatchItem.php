<?php

declare(strict_types=1);

namespace FriendsOfOuro\Http\Batch\Guzzle;

use FriendsOfOuro\Http\Batch\BatchItemInterface;
use FriendsOfOuro\Http\Batch\Guzzle\Exception\ExceptionUnavailableException;
use FriendsOfOuro\Http\Batch\Guzzle\Exception\ResponseUnavailableException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final readonly class BatchItem implements BatchItemInterface
{
    public function __construct(
        private RequestInterface $request,
        private ?ResponseInterface $response = null,
        private ?ClientExceptionInterface $exception = null,
    ) {
        if (null === $response && null === $exception) {
            throw new \InvalidArgumentException('Either response or exception must be provided');
        }

        if (null !== $response && null !== $exception) {
            throw new \InvalidArgumentException('Cannot have both response and exception');
        }
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function isSuccess(): bool
    {
        return null !== $this->response;
    }

    public function getResponse(): ResponseInterface
    {
        if (null === $this->response) {
            throw new ResponseUnavailableException('Response is not available for failed requests');
        }

        return $this->response;
    }

    public function getException(): ClientExceptionInterface
    {
        if (null === $this->exception) {
            throw new ExceptionUnavailableException('Exception is not available for successful requests');
        }

        return $this->exception;
    }
}
