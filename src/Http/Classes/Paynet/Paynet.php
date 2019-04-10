<?php
namespace Goodoneuz\PayUz\Http\Classes\Paynet;

use App;
use Goodoneuz\PayUz\Http\Classes\DataFormat;
use Goodoneuz\PayUz\Models\PaymentSystem;
use Goodoneuz\PayUz\Models\PaymentSystemParam;
use Goodoneuz\PayUz\Models\Transaction;
use Goodoneuz\PayUz\Services\PaymentSystemService;
use Goodoneuz\PayUz\Services\PaymentService;
use Goodoneuz\PayUz\Http\Classes\PaymentException;

class PaynetController
{
    public $config;
    public $request;
    public $response;
    public $merchant;
    public function __construct()
    {
        $this->config   = PaymentSystemService::getPaymentSystemParamsCollect(PaymentSystem::PAYNET);;
        $this->response  = new Response();
        $this->request  = new Request($this->response);
        $this->response->setRequest($request);
        $this->merchant = new Merchant($this->config, $this->request);
    }
    public function run(){
        $this->merchant->Authorize();
        switch ($this->request->params['method']) {
            case Request::METHOD_CheckTransaction:
                $this->CheckTransaction();
                break;
            case Request::METHOD_PerformTransaction:
                $this->PerformTransaction();
                break;
            case Request::METHOD_CancelTransaction:
                $this->CancelTransaction();
                break;
            case Request::METHOD_GetStatement:
                $this->GetStatement();
                break;
            case Request::METHOD_GetInformation:
                $this->GetInformation();
                break;
            default:
                throw new PaynetException(
                    null,
                    'Method not found.',
                    PaynetException::ERROR_METHOD_NOT_FOUND

                );
        }
    }

    private function CheckTransaction()
    {
        $transaction = $this->getTransactionBySystemTransactionId();
        $transactionState = ($transaction->state == Transaction::STATE_CANCELLED) ? 2 : 1;

        return  "<?xml version=\"1.0\" encoding=\"UTF-8\"?>".
            "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\">".
            "<soapenv:Body>".
            "<ns2:CheckTransactionResult xmlns:ns2=\"http://uws.provider.com/\">".
            "<errorMsg>Success</errorMsg>".
            "<status>0</status>".
            "<timeStamp>".$this->toDateTimeWithTimeZone(now())."</timeStamp>".
            "<providerTrnId>".$this->request->params['transactionId']."</providerTrnId>".
            "<transactionState>" . $transactionState . "</transactionState>".
            "<transactionStateErrorStatus>0</transactionStateErrorStatus>".
            "<transactionStateErrorMsg>Success</transactionStateErrorMsg>".
            "</ns2:CheckTransactionResult>".
            "</soapenv:Body>".
            "</soapenv:Envelope>";
    }


    private function PerformTransaction()
    {
        if ($this->getTransactionBySystemTransactionId())
            return  "<?xml version=\"1.0\" encoding=\"UTF-8\"?>".
                "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\">".
                "<soapenv:Body>".
                "<ns2:PerformTransactionResult xmlns:ns2=\"http://uws.provider.com/\">".
                "<errorMsg>bor trans</errorMsg>".
                "<status>201</status>".
                "<timeStamp>".$this->toDateTimeWithTimeZone(now())."</timeStamp>".
                "<providerTrnId>" . $this->request->params['transactionId'] . "</providerTrnId>".
                "</ns2:PerformTransactionResult>".
                "</soapenv:Body>".
                "</soapenv:Envelope>";
        
        $model = PaymentService::convertKeyToModel($this->request->params['key']);
        
        // TODO: check if user not found return status 302;
        
        if (is_null($model)) {
            return  "<?xml version=\"1.0\" encoding=\"UTF-8\"?>".
                "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\">".
                "<soapenv:Body>".
                "<ns2:PerformTransactionResult xmlns:ns2=\"http://uws.provider.com/\">".
                "<errorMsg>Not Found</errorMsg>".
                "<status>302</status>".
                "<timeStamp>".$this->toDateTimeWithTimeZone(now())."</timeStamp>".
                "<providerTrnId>0</providerTrnId>".
                "</ns2:PerformTransactionResult>".
                "</soapenv:Body>".
                "</soapenv:Envelope>";
        }
        // TODO: check if user be yuridik litso can not return status 501;

        $create_time                        = Format::timestamp(true);
        $transaction                        = new Transaction();
        $transaction->payment_system        = Transaction::PAYNET;
        $transaction->system_transaction_id = $this->request->params['transactionId'];
        $transaction->system_time           = Format::datetime2timestamp($this->request->params['transactionTime']);
        $transaction->system_time_datetime  = Format::timestamp2datetime($this->request->params['transactionTime']);
        $transaction->create_time           = Format::timestamp(true);
        $transaction->state                 = Transaction::STATE_CREATED;
        $transaction->amount                = 1 * $this->request->params['amount'];
        $transaction->currency_code         = Transaction::CODE_UZS;
        $transaction->user_key              = $this->request->params['key'];
        $transaction->exported              = Transaction::EXPORT_AVAILABLE;
        $transaction->save(); // after save $transaction->id will be populated with the newly created transaction's id.
        
        PaymentService::payListener(null,$transaction,'after-pay');

        return  "<?xml version=\"1.0\" encoding=\"UTF-8\"?>".
            "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\">".
            "<soapenv:Body>".
            "<ns2:PerformTransactionResult xmlns:ns2=\"http://uws.provider.com/\">".
            "<errorMsg>Success</errorMsg>".
            "<status>0</status>".
            "<timeStamp>".$this->toDateTimeWithTimeZone(now())."</timeStamp>".
            "<providerTrnId>" . $this->request->params['transactionId'] . "</providerTrnId>".
            "</ns2:PerformTransactionResult>".
            "</soapenv:Body>".
            "</soapenv:Envelope>";
    }

    private function CancelTransaction(){

        $transaction = $this->getTransactionBySystemTransactionId();

        if ($transaction == null || $transaction->state == Transaction::STATE_CANCELLED)
        {
            header('content-type: text/xml;');

            return  "<?xml version=\"1.0\" encoding=\"UTF-8\"?>".
                "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\">".
                "<soapenv:Body>".
                "<ns2:CancelTransactionResult xmlns:ns2=\"http://uws.provider.com/\">".
                "<errorMsg>bekor qilingan</errorMsg>".
                "<status>202</status>".
                "<timeStamp>".$this->toDateTimeWithTimeZone(now())."</timeStamp>".
                "<transactionState>2</transactionState>".
                "</ns2:CancelTransactionResult>".
                "</soapenv:Body>".
                "</soapenv:Envelope>";
        }

        $transaction->state = Transaction::STATE_CANCELLED;
        $transaction->update();
        PaymentService::payListener(null,$transaction,'cancel-pay');

        return  "<?xml version=\"1.0\" encoding=\"UTF-8\"?>".
            "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\">".
            "<soapenv:Body>".
            "<ns2:CancelTransactionResult xmlns:ns2=\"http://uws.provider.com/\">".
            "<errorMsg>Success</errorMsg>".
            "<status>0</status>".
            "<timeStamp>".$this->toDateTimeWithTimeZone(now())."</timeStamp>".
            "<transactionState>2</transactionState>".
            "</ns2:CancelTransactionResult>".
            "</soapenv:Body>".
            "</soapenv:Envelope>";
    }


    private function GetStatement()
    {
        
        $transactions = Transaction::where('payment_system', Transaction::PAYNET)
            ->where('state','<>',Transaction::STATE_CANCELLED)
            ->where('created_at','<=',$this->toDateTime($this->request->params['dateTo']))
            ->where('created_at','>=',$this->toDateTime($this->request->params['dateFrom']))
            ->get();
        $statements = '';

        foreach ($transactions as $transaction)
        {
            $statements = $statements .
                "<statements>".
                "<amount>" . $transaction->amount . "</amount>".
                "<providerTrnId>" . $transaction->id . "</providerTrnId>".
                "<transactionId>" . $transaction->system_transaction_id . "</transactionId>".
                "<transactionTime>".$this->toDateTimeWithTimeZone($transaction->created_at)."</transactionTime>".
                "</statements>";
        }
        return  "<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:ns1=\"http://uws.provider.com/\">".
            "<SOAP-ENV:Body>".
            "<ns1:GetStatementResult>".
            "<errorMsg>Success</errorMsg>".
            "<status>0</status>".
            "<timeStamp>".$this->toDateTimeWithTimeZone(now())."</timeStamp>".
            $statements .
            "</ns1:GetStatementResult>".
            "</SOAP-ENV:Body>".
            "</SOAP-ENV:Envelope>";
    }

    private function GetInformation(){
        $model = PaymentService::convertKeyToModel($this->request->params['key']);
        
        if ($model) {
            return  "<?xml version=\"1.0\" encoding=\"UTF-8\"?>".
                "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\">".
                "<soapenv:Body>".
                "<ns2:GetInformationResult xmlns:ns2=\"http://uws.provider.com/\">".
                "<errorMsg>Success</errorMsg>".
                "<status>0</status>".
                "<timeStamp>".$this->toDateTimeWithTimeZone(now())."</timeStamp>".
                "<parameters>".
                "<paramKey>userInfo</paramKey>".
                "<paramValue>".$model->name."</paramValue>".
                "</parameters>".
                "</ns2:GetInformationResult>".
                "</soapenv:Body>".
                "</soapenv:Envelope>";
        }else{
            return  "<?xml version=\"1.0\" encoding=\"UTF-8\"?>".
                "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\">".
                "<soapenv:Body>".
                "<ns2:GetInformationResult xmlns:ns2=\"http://uws.provider.com/\">".
                "<errorMsg>Not Found</errorMsg>".
                "<status>302</status>".
                "<timeStamp>".$this->toDateTimeWithTimeZone(now())."</timeStamp>".
                "</ns2:GetInformationResult>".
                "</soapenv:Body>".
                "</soapenv:Envelope>";
        }
    }
    

    private function getTransactionBySystemTransactionId()
    {
        return Transaction::where('system_transaction_id', $this->request->params['transactionId'])->first();
    }

    /**
     * @param $time '2018-11-06T17:39:31+05:00'
     * @return false|string
     */
    private function toDateTime($time){
        return date('Y-m-d H:i:s',strtotime($time));
    }

    /**
     * @param $time '2018-11-06 17:39:31'
     * @return string
     */
    private function toDateTimeWithTimeZone($time){
        return date('Y-m-d\TH:i:s',strtotime($time)) . '+05:00';
    }
}
