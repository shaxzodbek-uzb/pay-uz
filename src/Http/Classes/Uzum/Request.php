<?php

namespace Goodoneuz\PayUz\Http\Classes\Uzum;

class Request
{
    public $body;

    public function __construct()
    {
        $request_body  = file_get_contents('php://input');
        $this->body = json_decode($request_body, true);
    }

}
