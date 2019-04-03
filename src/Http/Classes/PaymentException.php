<?php
namespace Goodoneuz\PayUz\Http\Classes;


class PaymentException extends \Exception{

    public $response;
    
    public function __construct($response)
    {
        $this->response = $response;
    }
    
    public function setReponse($reponse)
    {
        $this->response = $reponse;
    }

    public function response(){
        return $this->response->send();
    }
    

}
