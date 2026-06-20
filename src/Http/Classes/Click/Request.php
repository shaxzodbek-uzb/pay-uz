<?php
namespace Goodoneuz\PayUz\Http\Classes\Click;


class Request{
    private $inputs;
    private $in_array;
    public function __construct()
    {
        // request()->getContent() works under php-fpm and Octane/RoadRunner alike,
        // unlike reading the raw php://input stream. See issue #71.
        $this->inputs = request()->getContent();
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