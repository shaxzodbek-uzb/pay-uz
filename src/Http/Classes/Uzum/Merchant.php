<?php

namespace Goodoneuz\PayUz\Http\Classes\Uzum;

use Goodoneuz\PayUz\Testing\CallbackAuth;

/**
 * Authenticates an incoming Uzum Bank Merchant API request:
 *   - HTTP Basic auth (login:password), and
 *   - the body serviceId must match the configured value.
 *
 * Both comparisons are constant-time to avoid timing / type-juggling bypass.
 * Basic auth is skipped only when a test turned it off in code
 * (CallbackAuth::skipForTests()), never because of APP_ENV.
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
        if (! CallbackAuth::isSkipped()) {
            // The current request's server vars, not the $_SERVER global, which
            // under Octane/RoadRunner/Swoole is the worker's. Any key with
            // AUTHORIZATION in it counts (REDIRECT_HTTP_AUTHORIZATION on Apache CGI).
            $auth = '';
            foreach (request()->server->all() as $key => $val) {
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
