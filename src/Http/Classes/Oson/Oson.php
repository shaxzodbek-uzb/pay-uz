<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Oson\Merchant;
use App\Http\Controllers\Oson\OsonException;
use App\Http\Controllers\Paycom\Format;
use App\Http\Controllers\Paycom\Request;
use App\Order;
use App\Transaction;
use Illuminate\Support\Facades\Log;

class Oson
{
    public $config;
    public $request;
    public $merchant;

    /**
     * OsonController constructor.
     * @param $request
     */
    public function __construct($request)
    {

        $this->config   = [
            'login' => env('OSON_LOGIN',null),
            'password'     => env('OSON_PASSWORD',null),
        ];
        $this->request  =  new Request();
        $this->merchant = new Merchant($this->config,$request['params']['acc']);
        Log::info('-----------------------------');
        Log::info('oson');
        Log::info('Payment Request: ');
        Log::info($this->request);
        Log::info('-----------------------------');
    }

    /**
     *
     */
    public function run(){
        try {
            $this->merchant->checkAuth();
            switch ($this->request['method']) {
                case 'user.check':
                    $this->CheckTransaction();
                    break;
                case 'transaction.perform':
                    $this->PayTransaction();
                    break;
                default:
                    throw new OsonException(OsonException::ERROR_UNKNOWN,[]);

            }
        }catch (OsonException $exception){
            $exception->send();
        }
    }
    public function CheckTransaction(){
        if (!isset($this->request['params']) || !isset($this->request['params']['info']))
            throw new OsonException(OsonException::ERROR_INVALID_AMOUNT,[]);

        $order = Order::where('id', $this->request['params']['info']['login'])->first(); //todo login may change in feature
        if ($order)
        {
            if($order->state == Order::STATE_PAY_ACCEPTED)
                throw new OsonException(OsonException::ERROR_ALREADY_PAID,[]);
            if ($order->state != Order::STATE_AVAILABLE)
                throw new OsonException(OsonException::ERROR_ORDER_NOT_AVAILABLE,[]);
        }

        throw new OsonException(OsonException::SUCCESS,['exist' => true]);
    }
    public function PayTransaction(){
        try {
            $this->CheckTransaction();
        } catch (OsonException $e) {
            if ($e->status != OsonException::SUCCESS)
                $e->send();
        }
        if (is_null($this->request['params']['trans']) || is_null($this->request['params']['trans']['time'])
            || is_null($this->request['params']['trans']['transID']))
            throw new OsonException(OsonException::ERROR_INSUFFICIENT_PRIVILEGE,[]);
        $order = Order::find($this->request['params']['trans']['login']); //todo login may change in feature

        if ($order){
            if ($order->state == Order::STATE_PAY_ACCEPTED)
                throw new OsonException(OsonException::ERROR_ALREADY_PAID, []);
            if ($order->state != Order::STATE_WAITING_PAY)
                throw new OsonException(OsonException::ERROR_ALREADY_PAID, []);
            if ($order->price != $this->request['params']['trans']['amount'])
                throw new OsonException(OsonException::ERROR_INVALID_AMOUNT, ['prividerTrnId' => $this->request['params']['trans']['transID'], 'ts' => $this->request['params']['trans']['time']]);
            $order->changeState(Order::STATE_PAY_ACCEPTED);
        }
        $create_time                        = Format::timestamp(true);
        $transaction                        = new Transaction();
        $transaction->payment_system        = Transaction::OSON;
        $transaction->system_transaction_id = $this->request['params']['trans']['transID'];
        $transaction->system_time           = Format::datetime2timestamp($this->request['params']['trans']['time']);
        $transaction->system_time_datetime  = $this->request['params']['trans']['time'];
        $transaction->create_time           = Format::timestamp2datetime($create_time);
        $transaction->state                 = Transaction::STATE_COMPLETED;
        $transaction->amount                = $this->request['params']['trans']['amount'];
        $transaction->currency_code         = Transaction::CODE_UZS;
        $transaction->order_id              = $this->request['params']['trans']['login'];
        $transaction->save(); // after save $transaction->id will be populated with the newly created transaction's id.
        $str = "Pul o'tkazishi  muvofaqqiyatli yakunlandi ✅";
        if ($order)
            $str =  $str
                ."\n<b>Buyurtma raqami 📥</b>: ".$order->id
                ."\n<b>Sug'urtalanuvchi 👥</b>: " . $order->user->detail->name
                ." \n<b>Summasi 💰:</b>".$order->price." sum ."
                ."\n<b>To'lov tizimi:</b>".$transaction->payment_system;
        else
            $str =  $str
                ."\n<b>Buyurtma raqami 📥</b>: ".$this->request['params']['trans']['login']
                ." \n<b>Summasi 💰:</b>".$this->request['params']['trans']['amount']." sum ."
                ."\n<b>To'lov tizimi:</b>".$transaction->payment_system;

        TelegramController::send($str);
        cURL::send_result($transaction);

        throw new OsonException(OsonException::SUCCESS,['prividerTrnId'=>$this->request['params']['trans']['transID'],'ts'=>$this->request['params']['trans']['time'],'order_state'=>Order::STATE_PAY_ACCEPTED]);

    }
    public static function getRedirectParams($order)
    {
        return [
            'merchant' => env('OSON_MERCHANT_ID'),
            'amount' => $order->price,
            'account' => $order->id,
            'description' => 'Kafolat Sug\'urta kompaniyasi',
            'callback' => route('payment.handle.result',['payment'=>'oson', 'type' => 'accept', 'order_id' => $order->id, 'state' => 'ok'])
        ];
    }
}
