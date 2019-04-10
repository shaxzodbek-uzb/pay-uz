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


    public function __construct()
    {
        $request_body  = file_get_contents('php://input');
        $clean_xml = str_ireplace(['soapenv:', 'soap:','xmlns:','xsi:','ns1:'], '', $request_body);
        $xml = simplexml_load_string($clean_xml);
        if ($xml){
            $body = $xml->Body;
        } else
            throw  new PaynetException(null, 'Error in request', PaynetException::ERROR_INVALID_JSON_RPC_OBJECT);
        $json_params = json_decode(json_encode($body),1);

        foreach ($json_params as $key => $value){
            switch ($key){
                case self::ARGUMENTS_PerformTransaction:
                    $this->paramsPerformTransaction($json_params[self::ARGUMENTS_PerformTransaction]);
                    break;
                case self::ARGUMENTS_CheckTransaction:
                    $this->paramsCheckTransaction($json_params[self::ARGUMENTS_CheckTransaction]);
                    break;
                case self::ARGUMENTS_GetStatement:
                    $this->paramsStament($json_params[self::ARGUMENTS_GetStatement]);
                    break;
                case self::ARGUMENTS_CancelTransaction:
                    $this->paramsCancel($json_params[self::ARGUMENTS_CancelTransaction]);
                    break;
                case self::ARGUMENTS_GetInformation:
                    $this->paramsInformation($json_params[self::ARGUMENTS_GetInformation]);
                    break;
                default:
                    throw new PaynetException(null, 'Error in method type', PaynetException::ERROR_METHOD_NOT_FOUND);
            }
            Log::info($key);
        }
    }

    public function paramsPerformTransaction($par){
        $this->params = [
            'method' => self::METHOD_PerformTransaction,
            'account' => [
                'login' => $par['username'],
                'password' => $par['password']
            ],
            'amount' => $par['amount'],
            'serviceId' => $par['serviceId'],
            'transactionId' => $par['transactionId'],
            'transactionTime' => $par['transactionTime'],
            'user_key' => $par['parameters']['paramValue']
        ];
    }
    public function paramsCheckTransaction($par){
        $this->params = [
            'method' => self::METHOD_CheckTransaction,
            'account' => [
                'login' => $par['username'],
                'password' => $par['password']
            ],
            'serviceId' => $par['serviceId'],
            'transactionId' => $par['transactionId'],
            'transactionTime' => $par['transactionTime'],
        ];
    }

    private function paramsStament($par)
    {
        $this->params = [
            'method' => self::METHOD_GetStatement,
            'account' => [
                'login' => $par['username'],
                'password' => $par['password']
            ],
            'serviceId' => $par['serviceId'],
            'dateFrom' => $par['dateFrom'],
            'dateTo' => $par['dateTo']
        ];
    }

    private function paramsCancel($par)
    {
        $res = [];
        $res1 = [
            'method' => self::METHOD_CancelTransaction,
            'account' => [
                'login' => $par['username'],
                'password' => $par['password']
            ],
            'serviceId' => $par['serviceId'],
            'transactionId' => $par['transactionId'],
            'transactionTime' => $par['transactionTime']
        ];
        $this->params = array_merge($res1, $res);
    }

    private function paramsInformation($par)
    {
        $res = [
            'method' => self::METHOD_GetInformation,
            'account' => [
                'login' => $par['username'],
                'password' => $par['password']
            ],
            'serviceId' => $par['serviceId'],
            'user_key' => $par['parameters']['paramValue']
        ];
        $this->params = $res;
    }

}
