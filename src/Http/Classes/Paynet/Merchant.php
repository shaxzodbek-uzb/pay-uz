<?php

namespace Goodoneuz\PayUz\Http\Classes\Paynet;

class Merchant
{
    public $config;
    public $request;

    public function __construct($config, $request)
    {
        $this->config = $config;
        $this->request = $request;
    }

    public function Authorize()
    {


        if ($this->config['login'] != $this->request->params['account']['login'] ||
            $this->config['password'] != $this->request->params['account']['password'])
        {
            throw new PaynetException(
                $this->request,
                'Insufficient privilege to perform this method.',
                PaynetException::ERROR_INSUFFICIENT_PRIVILEGE
            );
        }
        return true;
    }
}
