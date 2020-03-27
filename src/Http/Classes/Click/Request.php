<?php
namespace Goodoneuz\PayUz\Http\Classes\Click;


class Request{
    private $inputs;
    private $in_array;
    public function __construct()
    {
        $this->inputs = file_get_contents('php://input');
    }
    public function all(){
        if (!$this->in_array){
            $this->in_array = json_decode($this->inputs,true);
        }
        return $this->in_array;
    }
    public function __get($key){
        switch($key){
            case 'body':
                return $this->inputs; 
        }

    }
}