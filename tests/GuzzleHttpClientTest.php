<?php

declare(strict_types=1);

namespace FriendsOfOuro\Http\Batch\Guzzle\Tests;

use FriendsOfOuro\Http\Batch\Guzzle\Exception\ClientException;
use FriendsOfOuro\Http\Batch\Guzzle\Exception\RequestException;
use FriendsOfOuro\Http\Batch\Guzzle\GuzzleHttpClient;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception as MockException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

#[CoversClass(GuzzleHttpClient::class)]
final class GuzzleHttpClientTest extends TestCase
{
    public function test_constructor_with_default_client(): void
    {
        $this->expectNotToPerformAssertions();

        new GuzzleHttpClient();
    }

    /**
     * @throws MockException
     */
    public function test_constructor_with_custom_client(): void
    {
        $this->expectNotToPerformAssertions();

        $mockClient = $this->createMock(ClientInterface::class);
        new GuzzleHttpClient($mockClient);
    }

    public function test_with_filesystem_cache(): void
    {
        $this->expectNotToPerformAssertions();

        GuzzleHttpClient::withFilesystemCache('/tmp/test-cache');
    }

    public function test_with_apc_cache(): void
    {
        $this->expectNotToPerformAssertions();

        GuzzleHttpClient::withApcCache();
    }

    public function test_with_psr6_cache(): void
    {
        $this->expectNotToPerformAssertions();

        $pool = new ArrayAdapter();
        GuzzleHttpClient::withPsr6Cache($pool);
    }

    public function test_send_request_batch_success(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], 'Response 1'),
            new Response(200, [], 'Response 2'),
            new Response(200, [], 'Response 3'),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $mockClient = new Client(['handler' => $handlerStack]);

        $client = new GuzzleHttpClient($mockClient);

        $requests = [
            new Request('GET', 'http://example.com/1'),
            new Request('GET', 'http://example.com/2'),
            new Request('GET', 'http://example.com/3'),
        ];

        $responses = $client->sendRequestBatch($requests);

        $this->assertCount(3, $responses);
        $this->assertEquals('Response 1', $responses[0]->getBody()->getContents());
        $this->assertEquals('Response 2', $responses[1]->getBody()->getContents());
        $this->assertEquals('Response 3', $responses[2]->getBody()->getContents());
    }

    /**
     * @throws \Exception
     */
    public function test_send_request_batch_with_exception(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], 'Response 1'),
            new GuzzleRequestException('Request failed', new Request('GET', 'http://example.com/2')),
            new Response(200, [], 'Response 3'),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $mockClient = new Client(['handler' => $handlerStack]);

        $client = new GuzzleHttpClient($mockClient);

        $requests = [
            new Request('GET', 'http://example.com/1'),
            new Request('GET', 'http://example.com/2'),
            new Request('GET', 'http://example.com/3'),
        ];

        $this->expectException(GuzzleRequestException::class);
        $this->expectExceptionMessage('Request failed');

        $client->sendRequestBatch($requests);
    }

    /**
     * @throws \Exception
     */
    public function test_send_request_batch_empty_array(): void
    {
        $client = new GuzzleHttpClient();

        $responses = $client->sendRequestBatch([]);

        $this->assertCount(0, $responses);
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function test_send_request_success(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], 'Success response'),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $mockClient = new Client(['handler' => $handlerStack]);

        $client = new GuzzleHttpClient($mockClient);

        $request = new Request('GET', 'http://example.com');
        $response = $client->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success response', $response->getBody()->getContents());
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function test_send_request_throws_client_exception(): void
    {
        $request = new Request('GET', 'http://example.com');
        $guzzleException = new GuzzleClientException(
            'Client error',
            $request,
            new Response(400)
        );

        $mockHandler = new MockHandler([$guzzleException]);
        $handlerStack = HandlerStack::create($mockHandler);
        $mockClient = new Client(['handler' => $handlerStack]);

        $client = new GuzzleHttpClient($mockClient);

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Client error');

        $client->sendRequest($request);
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function test_send_request_throws_request_exception(): void
    {
        $request = new Request('GET', 'http://example.com');
        $guzzleException = new GuzzleRequestException('Request error', $request);

        $mockHandler = new MockHandler([$guzzleException]);
        $handlerStack = HandlerStack::create($mockHandler);
        $mockClient = new Client(['handler' => $handlerStack]);

        $client = new GuzzleHttpClient($mockClient);

        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('Request error');

        $client->sendRequest($request);
    }

    public function test_client_exception_get_response(): void
    {
        $request = new Request('GET', 'http://example.com');
        $response = new Response(400, [], 'Bad Request');
        $guzzleException = new GuzzleClientException('Client error', $request, $response);

        $clientException = new ClientException('Client error', 400, $guzzleException);

        $this->assertSame($response, $clientException->getResponse());
    }

    public function test_client_exception_get_response_with_null(): void
    {
        $request = new Request('GET', 'http://example.com');
        $guzzleException = new GuzzleRequestException('Request error', $request);

        $clientException = new ClientException('Client error', 400, $guzzleException);

        $this->assertNull($clientException->getResponse());
    }
}
