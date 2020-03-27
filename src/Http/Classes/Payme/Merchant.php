<?php

namespace Goodoneuz\PayUz\Http\Classes\Payme;


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
        if (env('APP_ENV') != 'testing'){
            $headers = $_SERVER;
            $auth = ''; //$headers['REDIRECT_REDIRECT_HTTP_AUTHORIZATION']
            foreach($headers as $key=>$val){
                if (strpos($key, 'AUTHORIZATION') !== false) {
                    $auth = $val;
                }
            }

            if (($auth == '') ||
                !preg_match('/^\s*Basic\s+(\S+)\s*$/i', $auth, $matches) ||
                base64_decode($matches[1]) != $this->config['login'] . ":" . $this->config['password'])
            {
                $this->response->error(Response::ERROR_INSUFFICIENT_PRIVILEGE, 'Insufficient privilege to perform this method.');
            }
        }
        return true;
    }
}
