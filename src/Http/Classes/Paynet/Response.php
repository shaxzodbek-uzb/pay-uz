<?php
/**
 * Created by PhpStorm.
 * User: Good One Sales
 * Date: 9/3/2018
 * Time: 12:26 PM
 */

namespace Goodoneuz\PayUz\Http\Classes\Paynet;

use Carbon\Carbon;
use App\Transaction;
use Goodoneuz\PayUz\Http\Classes\PaymentException;

class Response
{
    const ERROR_INTERNAL_SYSTEM         = -32400;
    const ERROR_INSUFFICIENT_PRIVILEGE  = -32504;
    const ERROR_INVALID_JSON_RPC_OBJECT = -32600;
    const ERROR_METHOD_NOT_FOUND        = -32601;
    const ERROR_INVALID_AMOUNT          = -31001;
    const ERROR_TRANSACTION_NOT_FOUND   = -31003;
    const ERROR_INVALID_ACCOUNT         = -31050;
    const ERROR_COULD_NOT_CANCEL        = -31007;
    const ERROR_COULD_NOT_PERFORM       = -31008;
    const SUCCESS                       = 0;

    public $request;
    public $body;
    public $code;

    public function response($request, $body, $code){
        $this->request = $request;
        $this->body  = $body;
        $this->code = $code;
        throw new PaymentException($this);
    }
    public static function makeResponse($body){
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>".
                    "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\">".
                    "<soapenv:Body>".
                        $body.
                    "</soapenv:Body>".
                "</soapenv:Envelope>";
    }
    public function send(){
        header('content-type: text/xml;');
        if ($this->request == null)
            echo 'error';
        else 
            echo $this->body;
        
        exit();
    }

    public function setRequest($request){
        return $this->request = $request;
    }
}
