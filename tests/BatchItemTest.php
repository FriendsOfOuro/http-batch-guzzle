<?php

declare(strict_types=1);

namespace FriendsOfOuro\Http\Batch\Guzzle\Tests;

use FriendsOfOuro\Http\Batch\Guzzle\BatchItem;
use FriendsOfOuro\Http\Batch\Guzzle\Exception\ExceptionUnavailableException;
use FriendsOfOuro\Http\Batch\Guzzle\Exception\ResponseUnavailableException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception as MockException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

#[CoversClass(BatchItem::class)]
final class BatchItemTest extends TestCase
{
    /**
     * @throws MockException
     */
    public function test_constructor_with_response_creates_success_item(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $item = new BatchItem($request, $response);

        $this->assertTrue($item->isSuccess());
        $this->assertSame($request, $item->getRequest());
        $this->assertSame($response, $item->getResponse());
    }

    /**
     * @throws MockException
     */
    public function test_constructor_with_exception_creates_failure_item(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $exception = $this->createMock(ClientExceptionInterface::class);

        $item = new BatchItem($request, null, $exception);

        $this->assertFalse($item->isSuccess());
        $this->assertSame($request, $item->getRequest());
        $this->assertSame($exception, $item->getException());
    }

    /**
     * @throws MockException
     */
    public function test_constructor_with_both_response_and_exception_throws_exception(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $exception = $this->createMock(ClientExceptionInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot have both response and exception');

        new BatchItem($request, $response, $exception);
    }

    /**
     * @throws MockException
     */
    public function test_constructor_with_neither_response_nor_exception_throws_exception(): void
    {
        $request = $this->createMock(RequestInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Either response or exception must be provided');

        new BatchItem($request);
    }

    /**
     * @throws MockException
     */
    public function test_get_response_on_failure_item_throws_exception(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $exception = $this->createMock(ClientExceptionInterface::class);

        $item = new BatchItem($request, null, $exception);

        $this->expectException(ResponseUnavailableException::class);
        $this->expectExceptionMessage('Response is not available for failed requests');

        $item->getResponse();
    }

    /**
     * @throws MockException
     */
    public function test_get_exception_on_success_item_throws_exception(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $item = new BatchItem($request, $response);

        $this->expectException(ExceptionUnavailableException::class);
        $this->expectExceptionMessage('Exception is not available for successful requests');

        $item->getException();
    }
}
