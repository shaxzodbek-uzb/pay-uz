<?php

namespace Goodoneuz\PayUz\Http\Classes\Uzum;

/**
 * Authenticates an incoming Uzum Bank Merchant API request:
 *   - HTTP Basic auth (login:password), and
 *   - the body serviceId must match the configured value.
 *
 * Both comparisons are constant-time to avoid timing / type-juggling bypass.
 * Authentication is skipped only under the package's own unit tests.
 */
class Merchant
{
    public $config;
    public $response;

    public function __construct($config, $response)
    {
        $this->config = $config;
        $this->response = $response;
    }

    public function Authorize($request)
    {
        if (! app()->runningUnitTests()) {
            $auth = '';
            foreach ($_SERVER as $key => $val) {
                if (strpos($key, 'AUTHORIZATION') !== false) {
                    $auth = $val;
                }
            }

            $expected = ($this->config['login'] ?? '') . ':' . ($this->config['password'] ?? '');

            if ($auth === '' ||
                !preg_match('/^\s*Basic\s+(\S+)\s*$/i', $auth, $matches) ||
                !hash_equals($expected, (string) base64_decode($matches[1]))) {
                $this->response->error(Response::ERROR_AUTH, 401);
            }
        }

        if (!hash_equals((string) ($this->config['service_id'] ?? ''), (string) ($request->serviceId ?? ''))) {
            $this->response->error(Response::ERROR_INVALID_SERVICE_ID);
        }

        return true;
    }
}
