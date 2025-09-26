<?php

declare(strict_types=1);

namespace FriendsOfOuro\Http\Batch\Guzzle\Exception;

use FriendsOfOuro\Http\Batch\Exception\ResponseUnavailableExceptionInterface;

class ResponseUnavailableException extends \RuntimeException implements ResponseUnavailableExceptionInterface
{
}
