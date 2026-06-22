<?php

namespace Goodoneuz\PayUz\Tests\Support;

use Goodoneuz\PayUz\Support\Http\HttpClient;

/**
 * Shared test transport: records each request and replays a queued response, so
 * JSON-RPC / HTTP driver tests can assert the exact wire payload + headers
 * without the network. Not a *Test.php file.
 */
class FakeHttpClient implements HttpClient
{
    /** @var array FIFO of canned responses */
    protected $responses = [];

    /** @var array|null last request ['url','payload','headers'] */
    public $lastRequest;

    /** @var array all requests in order */
    public $requests = [];

    /**
     * @param array $body
     * @param int   $status
     * @return self
     */
    public function queue(array $body, $status = 200)
    {
        $this->responses[] = ['status' => $status, 'body' => $body, 'raw' => json_encode($body)];

        return $this;
    }

    public function post($url, array $payload, array $headers = [])
    {
        return $this->record(['method' => 'POST', 'url' => $url, 'payload' => $payload, 'headers' => $headers, 'form' => false], $url);
    }

    public function postForm($url, array $fields, array $headers = [])
    {
        return $this->record(['method' => 'POST', 'url' => $url, 'payload' => $fields, 'headers' => $headers, 'form' => true], $url);
    }

    public function request($method, $url, $payload = null, array $headers = [])
    {
        return $this->record(['method' => $method, 'url' => $url, 'payload' => $payload, 'headers' => $headers, 'form' => false], $url);
    }

    /**
     * @param array  $request
     * @param string $url
     * @return array
     */
    protected function record(array $request, $url)
    {
        $this->lastRequest = $request;
        $this->requests[]  = $request;

        if (empty($this->responses)) {
            throw new \RuntimeException('FakeHttpClient: no response queued for '.$url);
        }

        return array_shift($this->responses);
    }
}
