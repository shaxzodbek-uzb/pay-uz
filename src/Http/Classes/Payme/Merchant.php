<?php

namespace Goodoneuz\PayUz\Http\Classes\Payme;


use Goodoneuz\PayUz\Http\Controllers;

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
        $headers = $_SERVER;

//        preg_match('/^\s*Basic\s+(\S+)\s*$/i', $headers['Authorization'], $matches);
//        echo $matches[1] . (base64_decode($matches[1]));
//        exit();
        if (!$headers ||
            !isset($headers['REDIRECT_REDIRECT_HTTP_AUTHORIZATION']) ||
            !preg_match('/^\s*Basic\s+(\S+)\s*$/i', $headers['REDIRECT_REDIRECT_HTTP_AUTHORIZATION'], $matches) ||
            base64_decode($matches[1]) != $this->config['login'] . ":" . $this->config['password'])
        {
            $this->response->error(Response::ERROR_INSUFFICIENT_PRIVILEGE, 'Insufficient privilege to perform this method.');
        }
        return true;
    }
}
