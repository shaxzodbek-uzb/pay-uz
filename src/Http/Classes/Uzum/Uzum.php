<?php
namespace Goodoneuz\PayUz\Http\Classes\Uzum;

use Goodoneuz\PayUz\Http\Classes\BaseGateway;

class Uzum extends BaseGateway
{
    public $method;
    public $request;

    public function __construct() {
        $this->method = request()->route()->parameters()['method'];
        $this->request = new Request();
    }   
    
    public function run(){
        match ($this->method) {
            'check' => $this->check(),
            'create' => $this->createTreansation(),
            'confirm' => $this->confirm(),
            'reverse' => $this->reverse(),
            'status' => $this->status(),
        };

    }

    public function check(){
        info('check');
    }

    public function createTreansation(){
        info('create');
    }

    public function confirm(){
        info('confirm');
    }

    public function reverse(){
        info('reverse');
    }

    public function status(){
        info('status');
    }
}
