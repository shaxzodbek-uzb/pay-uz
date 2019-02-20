<?php
namespace Goodoneuz\PayUz\Http\Classes\Click;

use Goodoneuz\PayUz\Http\Classes\PaymentException;
use Goodoneuz\PayUz\Http\Classes\DataFormat;
use Goodoneuz\PayUz\Models\Transaction;

class Click{
    
    private $merchant;
    private $request;
    private $response;
    const REQUEST_PREPARE = 0;
    const REQUEST_COMPLATE = 1;

    public function __construct($request)
    {
        $this->request  = $request;
        $this->response = new Response();
        $this->merchant = new Merchant($this->response);
        
    }


    public function run(){
        $required_fields = [
            'click_trans_id', 'service_id', 
            'click_paydoc_id', 'merchant_trans_id', 
            'amount', 'action', 'error', 'error_note', 
            'sign_time', 'sign_string'
        ];
        $res = $this->check_for_required_field($required_fields);
        if (!$res){
            $this->response->setResult(Response::ERROR_REQUEST_FROM);
        }
        $this->merchant->validateRequest($this->request->all());
        switch ($this->request->all()['action']) {
            case self::REQUEST_PREPARE:
                $this->Prepare();
                break;
            case self::REQUEST_COMPLATE:
                $this->Complete();
                break;
            default:
                $this->response->setResult(Response::ERROR_ACTION_NOT_FOUND);
        }
    }
    
    private function Prepare()
    {
        $params = $this->request->all();

        $additional_params = [
            'merchant_prepare_id' => null,
            'click_trans_id' => null,
            'merchant_trans_id' => null
        ];
        $order = Order::find($this->request['merchant_trans_id']);
        if(!$order)
            $this->response->setResult(Response::ERROR_ORDER_NOT_FOUND);

        if (!$order->isPayable($params['amount']))
            $this->response->setResult(Response::ERROR_INVALID_AMOUNT);
            
        $additional_params['click_trans_id'] = $params['click_trans_id'];
        $additional_params['merchant_trans_id'] = $params['merchant_trans_id'];
        
        $create_time                        = DataFormat::timestamp(true);
        $transaction                        = Transaction::create([
            'payment_system'        => Transaction::CLICK,
            'system_transaction_id' => $params['click_trans_id'],
            'amount'                => $params['amount'],
            'currency_code'         => Transaction::CURRENCY_CODE_UZS,
            'payable_type'          => 'App\Order', 
            'payable_id'            => $params['merchant_trans_id'],
            'state'                 => Transaction::STATE_CREATED,
            'create_time'           => DataFormat::timestamp(true), 
            'system_time_datetime'  => $params['sign_time'],
            'comment'               => $params['error_note'],
        ]);
        $additional_params['merchant_prepare_id'] = $transaction->id;
        $this->response->setResult(Response::SUCCESS,$additional_params);
    }
    private function Complete()
    {
        $params = $this->request->all();

        $additional_params = [
            'click_trans_id' => $params['click_trans_id'],
            'merchant_trans_id' => $params['merchant_trans_id'],
            'merchant_confirm_id' => null
        ];

        
        $transaction = Transaction::find($params['merchant_prepare_id']);
        if (!$transaction)
            $this->response->setResult(Response::ERROR_TRANSACTION_NOT_FOUND);
        
        if ($params['error'] == -1){
            $additional_params['error_note'] = $params['error_note'];
            $this->response->setResult(Response::ERROR_ALREADY_PAID);
        }

        if ($params['error'] == -5017){
            $additional_params['error_note'] = $params['error_note'];
            $transaction->state = Transaction::STATE_CANCELLED;
            $transaction->update();
            $this->response->setResult(Response::ERROR_TRANSACTION_CANCELLED);
        }
        
        if ($transaction->state == Transaction::STATE_CANCELLED)
            $this->response->setResult(Response::ERROR_TRANSACTION_CANCELLED);

        if ($transaction->state != Transaction::STATE_CREATED)
            $this->response->setResult(Response::ERROR_ALREADY_PAID);

        if ($transaction->amount != $params['amount']){
            $this->response->setResult(Response::ERROR_INVALID_AMOUNT);
        }

        $transaction->state = Transaction::STATE_COMPLETED;
        $transaction->update();
        $order = Order::find($params['merchant_trans_id']);
        $order->pay($transaction->id);
        $additional_params['merchant_confirm_id'] = $transaction->id;
        $this->response->setResult(Response::SUCCESS,$additional_params);
    }

    private function check_for_required_field($fields)
    {
        $arr = $this->request->all();

        if ($arr['action'] == self::REQUEST_COMPLATE)
            $fields[] = 'merchant_prepare_id';

        foreach ($fields as $field)
            if(!array_key_exists($field, $arr)){
                echo $field;
                return false;
            }

        return true;
    }
    
    public static function getRedirectParams($pay)
    {
        $config   = config('payment.click');
        $time = date('Y-m-d H:i:s', time());
        $sign = MD5($time . $config['secret_key'] .
        $config['service_id'] . $pay->amount); // todo change price obj
        return [
            'MERCHANT_TRANS_AMOUNT' => $pay->amount,
            'MERCHANT_ID' => $config['merchant_id'],
            'MERCHANT_USER_ID' => $config['merchant_user_id'],
            'MERCHANT_SERVICE_ID' => $config['service_id'],
            'MERCHANT_TRANS_ID' => $pay->order_id, //TODO change key
            'MERCHANT_TRANS_NOTE' => '',
            'MERCHANT_USER_PHONE' => '',
            'MERCHANT_USER_EMAIL' => '',
            'SIGN_TIME' => $time,
            'SIGN_STRING' => $sign,
            'RETURN_URL' => 'http://themall.uz'
        ];
    }
}   
