<?php
namespace Goodoneuz\PayUz\Http\Classes;


class PaymentException extends \Exception{

    private $reponse;
    
    public function __construct($response)
    {
        $this->response = $response;
    }
    
    public function setReponse($reponse)
    {
        $this->response = $reponse;
    }

    public function response()
    {
        $this->response->send();
        return $response;
    }
    

}