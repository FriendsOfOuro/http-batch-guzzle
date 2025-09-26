<?php

declare(strict_types=1);

namespace FriendsOfOuro\Http\Batch\Guzzle;

use FriendsOfOuro\Http\Batch\BatchItemInterface;
use FriendsOfOuro\Http\Batch\ResponseBatchInterface;

final readonly class ResponseBatch implements ResponseBatchInterface
{
    /**
     * @param BatchItemInterface[] $results
     */
    public function __construct(
        private array $results,
    ) {
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function isCompleteSuccess(): bool
    {
        return array_all($this->results, fn ($result) => $result->isSuccess());
    }

    public function hasAnyFailures(): bool
    {
        return array_any($this->results, fn ($result) => !$result->isSuccess());
    }

    public function hasAnySuccesses(): bool
    {
        foreach ($this->results as $result) {
            if ($result->isSuccess()) {
                return true;
            }
        }

        return false;
    }

    public function getResponses(): array
    {
        return array_map(
            fn (BatchItemInterface $result) => $result->getResponse(),
            array_filter($this->results, fn (BatchItemInterface $result) => $result->isSuccess())
        );
    }

    public function getExceptions(): array
    {
        return array_map(
            fn (BatchItemInterface $result) => $result->getException(),
            array_filter($this->results, fn (BatchItemInterface $result) => !$result->isSuccess())
        );
    }

    public function filter(callable $predicate): static
    {
        return new self(array_filter($this->results, $predicate));
    }

    public function count(): int
    {
        return count($this->results);
    }
}
