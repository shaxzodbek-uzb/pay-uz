<?php

namespace Goodoneuz\PayUz\Tests\Support;

use Goodoneuz\PayUz\Support\Http\HttpClient;
use Goodoneuz\PayUz\Support\Http\TransportException;

/**
 * Shared test transport that always throws a transport-level fault, to verify
 * that drivers/clients let TransportException propagate (a network drop is not a
 * business error). Not a *Test.php file.
 */
class ThrowingHttpClient implements HttpClient
{
    public function post($url, array $payload, array $headers = [])
    {
        throw new TransportException('connection refused');
    }

    public function postForm($url, array $fields, array $headers = [])
    {
        throw new TransportException('connection refused');
    }

    public function request($method, $url, $payload = null, array $headers = [])
    {
        throw new TransportException('connection refused');
    }
}
