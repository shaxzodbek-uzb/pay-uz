<?php

namespace Goodoneuz\PayUz\Http\Classes\Payme;

use Goodoneuz\PayUz\Testing\CallbackAuth;

class Merchant
{
    public $config;
    public $response;
    public function __construct($config, $response)
    {
        $this->config = $config;
        $this->response = $response;
        // read key from key file
    }

    public function Authorize()
    {
        // The auth check is skipped only when a test turned it off in code.
        // It must NOT be keyed on the environment: app()->runningUnitTests() is
        // just APP_ENV === 'testing', so a deployed app with APP_ENV=testing
        // would accept unauthenticated callbacks.
        if (! CallbackAuth::isSkipped()) {
            // Read the current request's server vars, not the $_SERVER global: under
            // Octane, RoadRunner or Swoole the global belongs to the worker, not to
            // this request. Any key with AUTHORIZATION in it counts, so Apache CGI's
            // REDIRECT_HTTP_AUTHORIZATION works too.
            $headers = request()->server->all();
            $auth = '';
            foreach($headers as $key=>$val){
                if (strpos($key, 'AUTHORIZATION') !== false) {
                    $auth = $val;
                }
            }

            $expected = $this->config['login'] . ":" . $this->config['password'];

            if (($auth == '') ||
                !preg_match('/^\s*Basic\s+(\S+)\s*$/i', $auth, $matches) ||
                // Constant-time compare to avoid timing / type-juggling on credentials.
                !hash_equals($expected, (string)base64_decode($matches[1])))
            {
                $this->response->error(Response::ERROR_INSUFFICIENT_PRIVILEGE, 'Insufficient privilege to perform this method.');
            }
        }
        return true;
    }
}
