<?php

namespace Goodoneuz\PayUz\Support\Http;

/**
 * A thin JSON-RPC 2.0 client over the {@see HttpClient} seam, used by JSON-RPC
 * integrations (Payme Subscribe, Paysys, …).
 *
 * It builds the `{id, method, params}` request, sends it with the caller's
 * headers, and returns the `result` object. A response carrying an `error`
 * member is turned into a {@see JsonRpcException} (with the numeric code and
 * `data`), so drivers can map specific codes to their own typed exceptions. A
 * transport-level failure surfaces as {@see TransportException} from the client.
 */
class JsonRpcClient
{
    /** @var HttpClient */
    protected $http;

    public function __construct(HttpClient $http)
    {
        $this->http = $http;
    }

    /**
     * Call a JSON-RPC method and return its `result` array.
     *
     * @param string $url
     * @param string $method
     * @param array  $params
     * @param array  $headers
     * @param int    $id
     * @return array
     * @throws JsonRpcException on an `error` response
     * @throws TransportException on a transport failure
     */
    public function call($url, $method, array $params = [], array $headers = [], $id = 1)
    {
        // Empty params must serialise as a JSON object ({}), not an array ([]).
        $payload = [
            'id'     => $id,
            'method' => $method,
            'params' => $params ? $params : new \stdClass(),
        ];

        $response = $this->http->post($url, $payload, $headers);
        $status   = isset($response['status']) ? (int) $response['status'] : 0;
        $body     = isset($response['body']) && is_array($response['body']) ? $response['body'] : [];

        if (isset($body['error']) && $body['error']) {
            $error = $body['error'];

            throw new JsonRpcException(
                $this->stringifyMessage(isset($error['message']) ? $error['message'] : 'JSON-RPC error'),
                isset($error['code']) ? $error['code'] : 0,
                isset($error['data']) ? $error['data'] : null,
                $body
            );
        }

        // A non-2xx response with neither a JSON-RPC error nor a result is a
        // transport-level failure (e.g. a 5xx HTML/empty body) — surface it rather
        // than silently returning an empty result that the caller reads as success.
        if (!array_key_exists('result', $body) && ($status < 200 || $status >= 300)) {
            throw new TransportException(sprintf('JSON-RPC HTTP %d with no result or error body.', $status));
        }

        return isset($body['result']) && is_array($body['result']) ? $body['result'] : [];
    }

    /**
     * Payme returns localised error messages as a {ru,uz,en} object; collapse to
     * a single string, preferring English then Russian then the first value.
     *
     * @param string|array $message
     * @return string
     */
    protected function stringifyMessage($message)
    {
        if (is_array($message)) {
            foreach (['en', 'ru', 'uz'] as $lang) {
                if (!empty($message[$lang])) {
                    return (string) $message[$lang];
                }
            }

            return (string) reset($message);
        }

        return (string) $message;
    }
}
