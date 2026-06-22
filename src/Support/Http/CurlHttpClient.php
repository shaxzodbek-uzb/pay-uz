<?php

namespace Goodoneuz\PayUz\Support\Http;

/**
 * Default {@see HttpClient} — a thin cURL JSON POST so the package keeps its tiny
 * dependency surface (no Guzzle). Integrations depend on the interface, so an app
 * that already has Guzzle/Laravel-HTTP can bind its own implementation instead.
 *
 * TLS verification is left at cURL's secure defaults (peer + host verified) and
 * is intentionally never disabled.
 */
class CurlHttpClient implements HttpClient
{
    /** @var int request timeout, seconds */
    protected $timeout;

    /**
     * @param int $timeout
     */
    public function __construct($timeout = 30)
    {
        $this->timeout = (int) $timeout;
    }

    /**
     * {@inheritdoc}
     */
    public function post($url, array $payload, array $headers = [])
    {
        return $this->send('POST', $url, json_encode($payload), 'application/json', $headers);
    }

    /**
     * {@inheritdoc}
     */
    public function postForm($url, array $fields, array $headers = [])
    {
        return $this->send('POST', $url, http_build_query($fields), 'application/x-www-form-urlencoded', $headers);
    }

    /**
     * {@inheritdoc}
     */
    public function request($method, $url, $payload = null, array $headers = [])
    {
        $body = $payload === null ? null : json_encode($payload);

        return $this->send($method, $url, $body, 'application/json', $headers);
    }

    /**
     * @param string      $method
     * @param string      $url
     * @param string|null $body
     * @param string      $contentType
     * @param array       $headers
     * @return array
     */
    protected function send($method, $url, $body, $contentType, array $headers)
    {
        if (!function_exists('curl_init')) {
            throw new TransportException('The cURL extension is required for the default HTTP client.');
        }

        $headerLines = ['Content-Type: '.$contentType, 'Accept: application/json'];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name.': '.$value;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headerLines,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $raw    = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error  = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new TransportException('HTTP request failed: '.$error);
        }

        $decoded = json_decode($raw, true);

        return [
            'status' => $status,
            'body'   => is_array($decoded) ? $decoded : [],
            'raw'    => (string) $raw,
        ];
    }
}
