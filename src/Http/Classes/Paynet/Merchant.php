<?php

namespace Goodoneuz\PayUz\Http\Classes\Paynet;

class Merchant
{
    public $config;
    public $request;
    public $response;

    public function __construct($config, $request, $response)
    {
        $this->config = $config;
        $this->request = $request;
        $this->response = $response;
    }

    public function Authorize()
    {
        // Constant-time compares to avoid timing / type-juggling auth bypass.
        if (
            !hash_equals((string)$this->config['login'], (string)($this->request->params['account']['login'] ?? '')) ||
            !hash_equals((string)$this->config['password'], (string)($this->request->params['account']['password'] ?? ''))
        ) {
            $this->response->response(
                $this->request,
                'Insufficient privilege to perform this method.',
                Response::ERROR_INSUFFICIENT_PRIVILEGE
            );
        }
        return true;
    }
}