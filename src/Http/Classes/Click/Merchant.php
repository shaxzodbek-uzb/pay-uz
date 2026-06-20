<?php

namespace Goodoneuz\PayUz\Http\Classes\Click;

use Goodoneuz\PayUz\Models\PaymentSystem;
use Goodoneuz\PayUz\Http\Classes\PaymentException;
use Goodoneuz\PayUz\Services\PaymentSystemService;

class Merchant{
    private $config;
    private $response;
    
    
    public function __construct($response)
    {
        $this->config   = PaymentSystemService::getPaymentSystemParamsCollect(PaymentSystem::CLICK);
        $this->response = $response;
    }
    public function validateRequest($request){
        $result = false;
        switch($request['action']){
            case Click::REQUEST_PREPARE:
                $result = $this->validatePrepareRequest($request);
                break;
            case Click::REQUEST_COMPLATE:
                $result = $this->validateCompleteRequest($request);
                break;
        }
        if((string)$request['service_id'] !== (string)($this->config['service_id'] ?? '') || !$result){
            $this->response->setResult(Response::ERROR_SIGN_CHECK);
        }
    }
    public function validatePrepareRequest($request)
    {
        $sign = md5($request['click_trans_id'] .
                    $request['service_id'] . $this->config['secret_key'] .
                    $request['merchant_trans_id'] . $request['amount'] .
                    $request['action'] . $request['sign_time']);
        // Constant-time compare: a loose == on hex hashes is vulnerable to PHP
        // type-juggling ("magic hash" 0e... collisions) and to timing analysis.
        return hash_equals($sign, (string)($request['sign_string'] ?? ''));
    }
    public function validateCompleteRequest($request)
    {
        $sign = md5(
            $request['click_trans_id'] . $request['service_id'] .
            $this->config['secret_key'] . $request['merchant_trans_id'] .
            $request['merchant_prepare_id'] . $request['amount'] .
            $request['action'] . $request['sign_time']);
        return hash_equals($sign, (string)($request['sign_string'] ?? ''));
    }
}
