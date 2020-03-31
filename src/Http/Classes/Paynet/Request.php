<?php
/**
 * Created by PhpStorm.
 * User: Good One Sales
 * Date: 9/3/2018
 * Time: 5:01 PM
 */

namespace Goodoneuz\PayUz\Http\Classes\Paynet;

class Request
{

    public $method;

    const ARGUMENTS_PerformTransaction  = 'PerformTransactionArguments';
    const ARGUMENTS_CheckTransaction    = 'CheckTransactionArguments';
    const ARGUMENTS_GetStatement        = 'GetStatementArguments';
    const ARGUMENTS_CancelTransaction   = 'CancelTransactionArguments';
    const ARGUMENTS_GetInformation      = 'GetInformationArguments';

    const METHOD_PerformTransaction     = 'PerformTransaction';
    const METHOD_CheckTransaction       = 'CheckTransaction';
    const METHOD_GetStatement           = 'GetStatement';
    const METHOD_CancelTransaction      = 'CancelTransaction';
    const METHOD_GetInformation         = 'GetInformation';

    public $params;
    private $response;

    public function __construct($response)
    {
        $this->response = $response;
        $this->params = [];
        $arr_params = $this->getRequestArray();
        $this->loadAccount($arr_params);

        foreach ($arr_params as $key => $value){
            switch ($key){
                case self::ARGUMENTS_PerformTransaction:
                    $this->paramsPerformTransaction($arr_params[self::ARGUMENTS_PerformTransaction]);
                    break;
                case self::ARGUMENTS_CheckTransaction:
                    $this->paramsCheckTransaction($arr_params[self::ARGUMENTS_CheckTransaction]);
                    break;
                case self::ARGUMENTS_GetStatement:
                    $this->paramsStament($arr_params[self::ARGUMENTS_GetStatement]);
                    break;
                case self::ARGUMENTS_CancelTransaction:
                    $this->paramsCancel($arr_params[self::ARGUMENTS_CancelTransaction]);
                    break;
                case self::ARGUMENTS_GetInformation:
                    $this->paramsInformation($arr_params[self::ARGUMENTS_GetInformation]);
                    break;
                default:
                    $this->response->response($this,'Error in request', Response::ERROR_METHOD_NOT_FOUND);
            }
        }
    }
    public function loadAccount($arr_params){
       $arr_params = array_values($arr_params)[0];

        $this->params['account'] = [
            'login' => $arr_params['username'],
            'password' => $arr_params['password']
        ];
        $this->params['serviceId'] = $arr_params['serviceId'];
    }
    public function getRequestArray(){
        $request_body  = file_get_contents('php://input');
        $clean_xml = str_ireplace(['soapenv:', 'soap:','xmlns:','xsi:','ns1:'], '', $request_body);
        $xml = simplexml_load_string($clean_xml);
        $body = null;
        if ($xml)
            $body = $xml->Body;
        else
            $this->response->response($this,'Error in request', Response::ERROR_INVALID_JSON_RPC_OBJECT);

        return json_decode(json_encode($body),1);
    }
    public function paramsPerformTransaction($par){
        $res = [
            'method' => self::METHOD_PerformTransaction,
            'amount' => $par['amount'],
            'transactionId' => $par['transactionId'],
            'transactionTime' => $par['transactionTime'],
            'key' => $par['parameters']['paramValue']
        ];
        $this->params = array_merge($this->params, $res);
    }
    public function paramsCheckTransaction($par){
        $res = [
            'method' => self::METHOD_CheckTransaction,
            'transactionId' => $par['transactionId'],
            'transactionTime' => $par['transactionTime'],
        ];
        $this->params = array_merge($this->params, $res);
    }

    private function paramsStament($par)
    {
        $res = [
            'method' => self::METHOD_GetStatement,
            'dateFrom' => $par['dateFrom'],
            'dateTo' => $par['dateTo']
        ];
        $this->params = array_merge($this->params, $res);
    }

    private function paramsCancel($par)
    {
        $res = [
            'method' => self::METHOD_CancelTransaction,
            'transactionId' => $par['transactionId'],
            'transactionTime' => $par['transactionTime']
        ];
        $this->params = array_merge($this->params, $res);
    }

    private function paramsInformation($par)
    {
        $res = [
            'method' => self::METHOD_GetInformation,
            'key' => $par['parameters']['paramValue']
        ];
        $this->params = array_merge($this->params, $res);
    }

}
