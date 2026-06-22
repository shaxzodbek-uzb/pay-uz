<?php

namespace Goodoneuz\PayUz\Tests\Fiscalization;

use Goodoneuz\PayUz\Support\Http\HttpClient;
use Goodoneuz\PayUz\Support\Http\TransportException;

/**
 * Test transport that always throws a transport-level fault, to exercise the
 * production failure surface (the cURL client throwing on a dead connection) end
 * to end through the driver and manager. Not a *Test.php file.
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
