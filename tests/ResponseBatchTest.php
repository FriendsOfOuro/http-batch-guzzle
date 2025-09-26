<?php

declare(strict_types=1);

namespace FriendsOfOuro\Http\Batch\Guzzle\Tests;

use FriendsOfOuro\Http\Batch\BatchItemInterface;
use FriendsOfOuro\Http\Batch\Guzzle\ResponseBatch;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception as MockException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;

#[CoversClass(ResponseBatch::class)]
final class ResponseBatchTest extends TestCase
{
    /**
     * @throws MockException
     */
    public function test_get_results_returns_all_items(): void
    {
        $item1 = $this->createMock(BatchItemInterface::class);
        $item2 = $this->createMock(BatchItemInterface::class);
        $items = [$item1, $item2];

        $batch = new ResponseBatch($items);

        $this->assertSame($items, $batch->getResults());
    }

    /**
     * @throws MockException
     */
    public function test_count_returns_correct_number(): void
    {
        $item1 = $this->createMock(BatchItemInterface::class);
        $item2 = $this->createMock(BatchItemInterface::class);
        $items = [$item1, $item2];

        $batch = new ResponseBatch($items);

        $this->assertCount(2, $batch);
        $this->assertEquals(2, $batch->count());
    }

    /**
     * @throws MockException
     */
    public function test_is_complete_success_returns_true_when_all_success(): void
    {
        $item1 = $this->createMock(BatchItemInterface::class);
        $item1->method('isSuccess')->willReturn(true);

        $item2 = $this->createMock(BatchItemInterface::class);
        $item2->method('isSuccess')->willReturn(true);

        $batch = new ResponseBatch([$item1, $item2]);

        $this->assertTrue($batch->isCompleteSuccess());
    }

    /**
     * @throws MockException
     */
    public function test_is_complete_success_returns_false_when_any_failure(): void
    {
        $item1 = $this->createMock(BatchItemInterface::class);
        $item1->method('isSuccess')->willReturn(true);

        $item2 = $this->createMock(BatchItemInterface::class);
        $item2->method('isSuccess')->willReturn(false);

        $batch = new ResponseBatch([$item1, $item2]);

        $this->assertFalse($batch->isCompleteSuccess());
    }

    /**
     * @throws MockException
     */
    public function test_has_any_failures_returns_true_when_any_failure(): void
    {
        $item1 = $this->createMock(BatchItemInterface::class);
        $item1->method('isSuccess')->willReturn(true);

        $item2 = $this->createMock(BatchItemInterface::class);
        $item2->method('isSuccess')->willReturn(false);

        $batch = new ResponseBatch([$item1, $item2]);

        $this->assertTrue($batch->hasAnyFailures());
    }

    /**
     * @throws MockException
     */
    public function test_has_any_failures_returns_false_when_all_success(): void
    {
        $item1 = $this->createMock(BatchItemInterface::class);
        $item1->method('isSuccess')->willReturn(true);

        $item2 = $this->createMock(BatchItemInterface::class);
        $item2->method('isSuccess')->willReturn(true);

        $batch = new ResponseBatch([$item1, $item2]);

        $this->assertFalse($batch->hasAnyFailures());
    }

    /**
     * @throws MockException
     */
    public function test_has_any_successes_returns_true_when_any_success(): void
    {
        $item1 = $this->createMock(BatchItemInterface::class);
        $item1->method('isSuccess')->willReturn(false);

        $item2 = $this->createMock(BatchItemInterface::class);
        $item2->method('isSuccess')->willReturn(true);

        $batch = new ResponseBatch([$item1, $item2]);

        $this->assertTrue($batch->hasAnySuccesses());
    }

    /**
     * @throws MockException
     */
    public function test_has_any_successes_returns_false_when_all_failures(): void
    {
        $item1 = $this->createMock(BatchItemInterface::class);
        $item1->method('isSuccess')->willReturn(false);

        $item2 = $this->createMock(BatchItemInterface::class);
        $item2->method('isSuccess')->willReturn(false);

        $batch = new ResponseBatch([$item1, $item2]);

        $this->assertFalse($batch->hasAnySuccesses());
    }

    /**
     * @throws MockException
     */
    public function test_get_responses_returns_only_successful_responses(): void
    {
        $response1 = $this->createMock(ResponseInterface::class);
        $response2 = $this->createMock(ResponseInterface::class);

        $successItem1 = $this->createMock(BatchItemInterface::class);
        $successItem1->method('isSuccess')->willReturn(true);
        $successItem1->method('getResponse')->willReturn($response1);

        $failureItem = $this->createMock(BatchItemInterface::class);
        $failureItem->method('isSuccess')->willReturn(false);

        $successItem2 = $this->createMock(BatchItemInterface::class);
        $successItem2->method('isSuccess')->willReturn(true);
        $successItem2->method('getResponse')->willReturn($response2);

        $batch = new ResponseBatch([$successItem1, $failureItem, $successItem2]);

        $responses = $batch->getResponses();

        $this->assertCount(2, $responses);
        $this->assertSame($response1, $responses[0]);
        $this->assertSame($response2, $responses[1]);
    }

    /**
     * @throws MockException
     */
    public function test_get_exceptions_returns_only_failed_exceptions(): void
    {
        $exception1 = $this->createMock(ClientExceptionInterface::class);
        $exception2 = $this->createMock(ClientExceptionInterface::class);

        $successItem = $this->createMock(BatchItemInterface::class);
        $successItem->method('isSuccess')->willReturn(true);

        $failureItem1 = $this->createMock(BatchItemInterface::class);
        $failureItem1->method('isSuccess')->willReturn(false);
        $failureItem1->method('getException')->willReturn($exception1);

        $failureItem2 = $this->createMock(BatchItemInterface::class);
        $failureItem2->method('isSuccess')->willReturn(false);
        $failureItem2->method('getException')->willReturn($exception2);

        $batch = new ResponseBatch([$successItem, $failureItem1, $failureItem2]);

        $exceptions = $batch->getExceptions();

        $this->assertCount(2, $exceptions);
        $this->assertSame($exception1, $exceptions[0]);
        $this->assertSame($exception2, $exceptions[1]);
    }

    /**
     * @throws MockException
     */
    public function test_filter_returns_new_instance_with_filtered_items(): void
    {
        $item1 = $this->createMock(BatchItemInterface::class);
        $item1->method('isSuccess')->willReturn(true);

        $item2 = $this->createMock(BatchItemInterface::class);
        $item2->method('isSuccess')->willReturn(false);

        $item3 = $this->createMock(BatchItemInterface::class);
        $item3->method('isSuccess')->willReturn(true);

        $batch = new ResponseBatch([$item1, $item2, $item3]);

        $filteredBatch = $batch->filter(fn (BatchItemInterface $item) => $item->isSuccess());

        $this->assertInstanceOf(ResponseBatch::class, $filteredBatch);
        $this->assertNotSame($batch, $filteredBatch);
        $this->assertCount(2, $filteredBatch);

        $filteredResults = $filteredBatch->getResults();
        $this->assertSame($item1, $filteredResults[0]);
        $this->assertSame($item3, $filteredResults[1]);
    }

    public function test_empty_batch_behavior(): void
    {
        $batch = new ResponseBatch([]);

        $this->assertCount(0, $batch);
        $this->assertTrue($batch->isCompleteSuccess());
        $this->assertFalse($batch->hasAnyFailures());
        $this->assertFalse($batch->hasAnySuccesses());
        $this->assertCount(0, $batch->getResponses());
        $this->assertCount(0, $batch->getExceptions());

        $filteredBatch = $batch->filter(fn () => true);
        $this->assertInstanceOf(ResponseBatch::class, $filteredBatch);
        $this->assertCount(0, $filteredBatch);
    }
}
