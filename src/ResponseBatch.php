<?php

declare(strict_types=1);

namespace FriendsOfOuro\Http\Batch\Guzzle;

use FriendsOfOuro\Http\Batch\BatchItemInterface;
use FriendsOfOuro\Http\Batch\ResponseBatchInterface;

final readonly class ResponseBatch implements ResponseBatchInterface, \Countable
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
        foreach ($this->results as $result) {
            if (!$result->isSuccess()) {
                return false;
            }
        }

        return true;
    }

    public function hasAnyFailures(): bool
    {
        foreach ($this->results as $result) {
            if (!$result->isSuccess()) {
                return true;
            }
        }

        return false;
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

    public function getSuccessfulResults(): array
    {
        return array_filter($this->results, fn (BatchItemInterface $result) => $result->isSuccess());
    }

    public function getFailedResults(): array
    {
        return array_filter($this->results, fn (BatchItemInterface $result) => !$result->isSuccess());
    }

    public function filter(callable $predicate): array
    {
        return array_filter($this->results, $predicate);
    }

    public function count(): int
    {
        return count($this->results);
    }
}
