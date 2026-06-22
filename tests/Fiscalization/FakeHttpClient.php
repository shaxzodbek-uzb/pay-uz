<?php

namespace Goodoneuz\PayUz\Tests\Fiscalization;

use Goodoneuz\PayUz\Support\Http\HttpClient;

/**
 * Test transport: records the last request and replays a queued response, so
 * driver tests can assert the exact wire payload without touching the network.
 *
 * Not a *Test.php file, so PHPUnit does not collect it as a test case.
 */
class FakeHttpClient implements HttpClient
{
    /** @var array list of canned responses to return, FIFO */
    protected $responses = [];

    /** @var array the last request: ['url','payload','headers'] */
    public $lastRequest;

    /** @var array all requests in order */
    public $requests = [];

    /**
     * @param array $body   decoded JSON body to return
     * @param int   $status HTTP status
     * @return self
     */
    public function queue(array $body, $status = 200)
    {
        $this->responses[] = [
            'status' => $status,
            'body'   => $body,
            'raw'    => json_encode($body),
        ];

        return $this;
    }

    public function post($url, array $payload, array $headers = [])
    {
        $this->lastRequest = ['url' => $url, 'payload' => $payload, 'headers' => $headers];
        $this->requests[]  = $this->lastRequest;

        // Fail loudly on an under-queued test rather than returning a phantom
        // 200 that the driver would parse as a spurious success.
        if (empty($this->responses)) {
            throw new \RuntimeException('FakeHttpClient: no response queued for '.$url);
        }

        return array_shift($this->responses);
    }

    public function postForm($url, array $fields, array $headers = [])
    {
        // Not used by fiscalization tests; present to satisfy the interface.
        return $this->post($url, $fields, $headers);
    }

    public function request($method, $url, $payload = null, array $headers = [])
    {
        return $this->post($url, (array) $payload, $headers);
    }
}
