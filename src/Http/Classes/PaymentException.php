<?php
namespace Goodoneuz\PayUz\Http\Classes;


class PaymentException extends \Exception{

    private $response;
    
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
        return $this->response;
    }
    

}
