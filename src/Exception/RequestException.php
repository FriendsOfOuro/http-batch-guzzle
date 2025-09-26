<?php

declare(strict_types=1);

namespace FriendsOfOuro\Http\Batch\Guzzle\Exception;

use Psr\Http\Client\ClientExceptionInterface;

class RequestException extends \RuntimeException implements ClientExceptionInterface
{
}
