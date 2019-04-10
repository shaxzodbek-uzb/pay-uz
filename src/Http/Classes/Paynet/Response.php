<?php
/**
 * Created by PhpStorm.
 * User: Good One Sales
 * Date: 9/3/2018
 * Time: 12:26 PM
 */

namespace Goodoneuz\PayUz\Http\Classes\Paynet;

use App\Transaction;
use Carbon\Carbon;

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
    public $error;
    public $data;

    public function __construct($request, $message, $code, $data = null)
    {
        $this->request  = $request;
        $this->message  = $message;
        $this->code     = $code;
        $this->data     = $data;

        $this->error = ['code' => $this->code];

        if ($this->message) {
            $this->error['message'] = $this->message;
        }

        if ($this->data) {
            $this->error['data'] = $this->data;
        }
    }

    public function send()
    {
        $response = '';
        if ($this->request == null){
            echo 'error';
            exit();
        }
        header('content-type: text/xml;');
        echo $response;
        exit();
    }

    public function setRequest($request){
        return $this->request = $request;
    }
}
