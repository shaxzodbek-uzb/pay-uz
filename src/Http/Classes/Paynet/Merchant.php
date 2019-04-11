<?php

namespace Goodoneuz\PayUz\Http\Classes\Paynet;

class Merchant
{
    public $config;
    public $request;

    public function __construct($config, $request, $response)
    {
        $this->config = $config;
        $this->request = $request;
        $this->response = $response;
    }

    public function Authorize()
    {


        if ($this->config['login'] != $this->request->params['account']['login'] ||
            $this->config['password'] != $this->request->params['account']['password'])
        {
            $this->resonse->response($this->request, 'Insufficient privilege to perform this method.', Response::ERROR_INSUFFICIENT_PRIVILEGE);
        }
        return true;
    }
}
