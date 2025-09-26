<?php

declare(strict_types=1);

namespace FriendsOfOuro\Http\Batch\Guzzle;

use FriendsOfOuro\Http\Batch\ClientInterface as BatchClientInterface;
use FriendsOfOuro\Http\Batch\ResponseBatchInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Pool;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\Psr6CacheStorage;
use Kevinrob\GuzzleCache\Strategy\PublicCacheStrategy;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

final readonly class GuzzleHttpClient implements BatchClientInterface
{
    private ClientInterface $client;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?: new Client([
            'handler' => new CurlMultiHandler(),
        ]);
    }

    public static function withFilesystemCache(string $path, array $config = []): self
    {
        return self::withPsr6Cache(
            new FilesystemAdapter(directory: $path),
            $config
        );
    }

    public static function withApcCache(array $config = []): self
    {
        return self::withPsr6Cache(
            new ApcuAdapter(),
            $config
        );
    }

    public static function withPsr6Cache(CacheItemPoolInterface $pool, array $config = []): self
    {
        $stack = new HandlerStack(new CurlMultiHandler());

        $stack->push(
            new CacheMiddleware(new PublicCacheStrategy(new Psr6CacheStorage($pool))),
            'cache'
        );

        $client = new Client(array_merge(
            ['handler' => $stack],
            $config
        ));

        return new self($client);
    }

    public function sendRequestBatch(array $requests): ResponseBatchInterface
    {
        $responses = Pool::batch(
            $this->client,
            $requests
        );

        $batchItems = [];
        foreach ($requests as $index => $request) {
            $response = $responses[$index];

            if ($response instanceof \Exception) {
                // Convert Guzzle exceptions to PSR-18 ClientExceptionInterface
                $clientException = $this->convertException($response);
                $batchItems[] = new BatchItem($request, null, $clientException);
            } else {
                assert($response instanceof ResponseInterface, new \LogicException('Expected ResponseInterface'));
                $batchItems[] = new BatchItem($request, $response);
            }
        }

        return new ResponseBatch($batchItems);
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        try {
            return $this->client->send($request);
        } catch (GuzzleClientException $e) {
            throw new Exception\ClientException($e->getMessage(), $e->getCode(), $e);
        } catch (GuzzleRequestException $e) {
            throw new Exception\RequestException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function convertException(\Exception $exception): ClientExceptionInterface
    {
        return match (true) {
            $exception instanceof GuzzleClientException => new Exception\ClientException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            ),
            default => new Exception\RequestException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            ),
        };
    }
}
