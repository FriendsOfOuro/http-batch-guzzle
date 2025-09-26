<?php

declare(strict_types=1);

namespace FriendsOfOuro\Http\Batch\Guzzle;

use FriendsOfOuro\Http\Batch\ClientInterface;
use FriendsOfOuro\Http\Batch\ResponseBatchInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP client spy that records all requests, responses, and exceptions.
 *
 * This is a decorator/spy pattern implementation that wraps another HTTP client
 * to record all interactions for testing and debugging purposes.
 */
final class RecordingHttpClient implements ClientInterface
{
    /** @var RequestInterface[] */
    private array $requests = [];

    /** @var ResponseInterface[] */
    private array $responses = [];

    /** @var ClientExceptionInterface[] */
    private array $exceptions = [];

    /** @var array<int, array{request: RequestInterface, response?: ResponseInterface, exception?: ClientExceptionInterface}> */
    private array $interactions = [];

    /** @var array<RequestInterface[]> */
    private array $batchRequests = [];

    /** @var ResponseBatchInterface[] */
    private array $batchResponses = [];

    /** @var ClientExceptionInterface[] */
    private array $batchExceptions = [];

    /** @var array<int, array{requests: RequestInterface[], batch?: ResponseBatchInterface, exception?: ClientExceptionInterface}> */
    private array $batchInteractions = [];

    public function __construct(private readonly ClientInterface $inner)
    {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        try {
            $response = $this->inner->sendRequest($request);
            $this->responses[] = $response;
            $this->interactions[] = [
                'request' => $request,
                'response' => $response,
            ];

            return $response;
        } catch (ClientExceptionInterface $exception) {
            $this->exceptions[] = $exception;
            $this->interactions[] = [
                'request' => $request,
                'exception' => $exception,
            ];

            throw $exception;
        }
    }

    public function sendRequestBatch(array $requests): ResponseBatchInterface
    {
        $this->batchRequests[] = $requests;

        try {
            $batch = $this->inner->sendRequestBatch($requests);
            $this->batchResponses[] = $batch;
            $this->batchInteractions[] = [
                'requests' => $requests,
                'batch' => $batch,
            ];

            // Also record individual requests and responses from the batch
            foreach ($batch->getResults() as $result) {
                $this->requests[] = $result->getRequest();

                if ($result->isSuccess()) {
                    $response = $result->getResponse();
                    $this->responses[] = $response;
                    $this->interactions[] = [
                        'request' => $result->getRequest(),
                        'response' => $response,
                    ];
                } else {
                    $exception = $result->getException();
                    $this->exceptions[] = $exception;
                    $this->interactions[] = [
                        'request' => $result->getRequest(),
                        'exception' => $exception,
                    ];
                }
            }

            return $batch;
        } catch (ClientExceptionInterface $exception) {
            $this->batchExceptions[] = $exception;
            $this->batchInteractions[] = [
                'requests' => $requests,
                'exception' => $exception,
            ];

            throw $exception;
        }
    }

    /**
     * Get all recorded single requests in chronological order.
     *
     * @return RequestInterface[]
     */
    public function getRecordedRequests(): array
    {
        return $this->requests;
    }

    /**
     * Get all recorded successful responses in chronological order.
     *
     * @return ResponseInterface[]
     */
    public function getRecordedResponses(): array
    {
        return $this->responses;
    }

    /**
     * Get all recorded exceptions in chronological order.
     *
     * @return ClientExceptionInterface[]
     */
    public function getRecordedExceptions(): array
    {
        return $this->exceptions;
    }

    /**
     * Get all single request interactions (requests with their responses or exceptions) in chronological order.
     *
     * @return array<int, array{request: RequestInterface, response?: ResponseInterface, exception?: ClientExceptionInterface}>
     */
    public function getInteractions(): array
    {
        return $this->interactions;
    }

    /**
     * Get all recorded batch requests in chronological order.
     *
     * @return array<RequestInterface[]>
     */
    public function getRecordedBatchRequests(): array
    {
        return $this->batchRequests;
    }

    /**
     * Get all recorded batch responses in chronological order.
     *
     * @return ResponseBatchInterface[]
     */
    public function getRecordedBatchResponses(): array
    {
        return $this->batchResponses;
    }

    /**
     * Get all recorded batch exceptions in chronological order.
     *
     * @return ClientExceptionInterface[]
     */
    public function getRecordedBatchExceptions(): array
    {
        return $this->batchExceptions;
    }

    /**
     * Get all batch interactions (batch requests with their responses or exceptions) in chronological order.
     *
     * @return array<int, array{requests: RequestInterface[], batch?: ResponseBatchInterface, exception?: ClientExceptionInterface}>
     */
    public function getBatchInteractions(): array
    {
        return $this->batchInteractions;
    }

    /**
     * Get the most recent request, or null if no requests have been made.
     */
    public function getLastRequest(): ?RequestInterface
    {
        return end($this->requests) ?: null;
    }

    /**
     * Get the most recent successful response, or null if no successful responses.
     */
    public function getLastResponse(): ?ResponseInterface
    {
        return end($this->responses) ?: null;
    }

    /**
     * Get the most recent exception, or null if no exceptions occurred.
     */
    public function getLastException(): ?ClientExceptionInterface
    {
        return end($this->exceptions) ?: null;
    }

    /**
     * Get the most recent interaction (request with response or exception).
     *
     * @return array{request: RequestInterface, response?: ResponseInterface, exception?: ClientExceptionInterface}|null
     */
    public function getLastInteraction(): ?array
    {
        return end($this->interactions) ?: null;
    }

    /**
     * Get the most recent batch request, or null if no batch requests have been made.
     *
     * @return RequestInterface[]|null
     */
    public function getLastBatchRequest(): ?array
    {
        return end($this->batchRequests) ?: null;
    }

    /**
     * Get the most recent batch response, or null if no batch responses.
     */
    public function getLastBatchResponse(): ?ResponseBatchInterface
    {
        return end($this->batchResponses) ?: null;
    }

    /**
     * Get the most recent batch exception, or null if no batch exceptions occurred.
     */
    public function getLastBatchException(): ?ClientExceptionInterface
    {
        return end($this->batchExceptions) ?: null;
    }

    /**
     * Get the most recent batch interaction.
     *
     * @return array{requests: RequestInterface[], batch?: ResponseBatchInterface, exception?: ClientExceptionInterface}|null
     */
    public function getLastBatchInteraction(): ?array
    {
        return end($this->batchInteractions) ?: null;
    }

    /**
     * Get count of recorded requests (including those from batches).
     */
    public function getRequestCount(): int
    {
        return count($this->requests);
    }

    /**
     * Get count of successful responses (including those from batches).
     */
    public function getResponseCount(): int
    {
        return count($this->responses);
    }

    /**
     * Get count of exceptions (including those from batches).
     */
    public function getExceptionCount(): int
    {
        return count($this->exceptions);
    }

    /**
     * Get count of batch requests.
     */
    public function getBatchRequestCount(): int
    {
        return count($this->batchRequests);
    }

    /**
     * Get count of batch responses.
     */
    public function getBatchResponseCount(): int
    {
        return count($this->batchResponses);
    }

    /**
     * Get count of batch exceptions.
     */
    public function getBatchExceptionCount(): int
    {
        return count($this->batchExceptions);
    }

    /**
     * Clear all recorded data.
     */
    public function clearRecordings(): void
    {
        $this->requests = [];
        $this->responses = [];
        $this->exceptions = [];
        $this->interactions = [];
        $this->batchRequests = [];
        $this->batchResponses = [];
        $this->batchExceptions = [];
        $this->batchInteractions = [];
    }

    /**
     * Check if any exceptions were recorded (including batch exceptions).
     */
    public function hasExceptions(): bool
    {
        return !empty($this->exceptions);
    }

    /**
     * Check if any batch exceptions were recorded.
     */
    public function hasBatchExceptions(): bool
    {
        return !empty($this->batchExceptions);
    }

    /**
     * Get requests that resulted in exceptions.
     *
     * @return RequestInterface[]
     */
    public function getFailedRequests(): array
    {
        return array_values(array_map(
            fn (array $interaction): RequestInterface => $interaction['request'],
            array_filter(
                $this->interactions,
                fn (array $interaction): bool => isset($interaction['exception'])
            )
        ));
    }

    /**
     * Get requests that resulted in successful responses.
     *
     * @return RequestInterface[]
     */
    public function getSuccessfulRequests(): array
    {
        return array_values(array_map(
            fn (array $interaction): RequestInterface => $interaction['request'],
            array_filter(
                $this->interactions,
                fn (array $interaction): bool => isset($interaction['response'])
            )
        ));
    }

    /**
     * Get batch requests that resulted in exceptions.
     *
     * @return array<RequestInterface[]>
     */
    public function getFailedBatchRequests(): array
    {
        return array_values(array_map(
            fn (array $interaction): array => $interaction['requests'],
            array_filter(
                $this->batchInteractions,
                fn (array $interaction): bool => isset($interaction['exception'])
            )
        ));
    }

    /**
     * Get batch requests that resulted in successful responses.
     *
     * @return array<RequestInterface[]>
     */
    public function getSuccessfulBatchRequests(): array
    {
        return array_values(array_map(
            fn (array $interaction): array => $interaction['requests'],
            array_filter(
                $this->batchInteractions,
                fn (array $interaction): bool => isset($interaction['batch'])
            )
        ));
    }
}