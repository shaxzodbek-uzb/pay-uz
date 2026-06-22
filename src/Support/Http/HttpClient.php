<?php

namespace Goodoneuz\PayUz\Support\Http;

/**
 * Minimal HTTP transport seam shared by the package's outbound integrations
 * (fiscalization, card tokenization, aggregators, e-invoicing).
 *
 * The package deliberately avoids a hard Guzzle dependency (it only requires
 * illuminate/support), so the default implementation is a small cURL wrapper.
 * Tests inject a fake transport to assert the exact request payload and to feed
 * canned responses without touching the network. A transport-level failure (no
 * response at all) is signalled with {@see TransportException}; HTTP error
 * statuses are returned, not thrown, so each caller decides how to treat them.
 */
interface HttpClient
{
    /**
     * POST a JSON payload and return the decoded response:
     *   [
     *     'status' => int,    // HTTP status code
     *     'body'   => array,  // JSON-decoded response body ([] if not JSON)
     *     'raw'    => string,  // raw response body
     *   ]
     *
     * @param string $url
     * @param array  $payload  JSON-serialisable request body
     * @param array  $headers  ['Header-Name' => 'value', ...]
     * @return array
     * @throws TransportException on a transport-level failure (no response).
     */
    public function post($url, array $payload, array $headers = []);

    /**
     * POST application/x-www-form-urlencoded fields (e.g. an OAuth2
     * client_credentials token request). Same return shape as {@see post()}.
     *
     * @param string $url
     * @param array  $fields   key => value form fields
     * @param array  $headers
     * @return array
     * @throws TransportException on a transport-level failure (no response).
     */
    public function postForm($url, array $fields, array $headers = []);

    /**
     * Issue a request with an explicit HTTP method (GET/PUT/DELETE/POST), JSON
     * body. Pass $payload = null for a bodyless request (e.g. GET/DELETE). Same
     * return shape as {@see post()}.
     *
     * @param string     $method
     * @param string     $url
     * @param array|null $payload JSON-serialisable body, or null for none
     * @param array      $headers
     * @return array
     * @throws TransportException on a transport-level failure (no response).
     */
    public function request($method, $url, $payload = null, array $headers = []);
}
