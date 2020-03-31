<?php

namespace Goodoneuz\PayUz\Http\Classes\Payme;

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
    const ERROR_INVALID_TRANSACTION     = -31051;
    const ERROR_COULD_NOT_CANCEL        = -31007;
    const ERROR_COULD_NOT_PERFORM       = -31008;

    /**
     * @var array
     */
    public $response;


    /**
     * Response constructor.
     */
    public function __construct()
    {
        $this->response = [];
        $this->response['jsonrpc'] = '2.0';
    }


    /**
     *
     */
    public function send()
    {
        if(env('APP_ENV') != 'testing')
            header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($this->response);
    }

    /**
     * @param $code
     * @param null $message
     * @param null $data
     * @throws PaymentException
     */
    public function error($code, $message = null, $data = null)
    {
        // prepare error data
        $error = ['code' => $code];

        if ($message)
            $error['message'] = $message;
        
        if ($data) 
            $error['data'] = $data;

        $this->response['result'] = null;
        $this->response['error']  = $error;
        throw new PaymentException($this);
    }

    /**
     * @param $result
     * @throws PaymentException
     */
    public function success($result){
        $this->response['result']  = $result;
        $this->response['error']   = null;
        throw new PaymentException($this);
    }

    /**
     * @param $ru
     * @param string $uz
     * @param string $en
     * @return array
     */
    public static function message($ru, $uz = '', $en = '')
    {
        return ['ru' => $ru, 'uz' => $uz, 'en' => $en];
    }

    /**
     * @param $request
     */
    public function setRequest($request)
    {
        $this->response['id'] = $request->id;
    }
}
