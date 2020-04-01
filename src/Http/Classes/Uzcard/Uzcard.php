<?php

namespace App\Http\Classes;

use App\Order;
use App\Transaction;
use Goodoneuz\PayUz\Http\Classes\BaseGateway;
use Goodoneuz\PayUz\Http\Classes\Paycom\Format;
use Goodoneuz\PayUz\Http\Classes\Paycom\Request;
use Goodoneuz\PayUz\Http\Classes\Uzcard\Merchant;
use Goodoneuz\PayUz\Http\Classes\Uzcard\WoywoException;


class WoywoController extends BaseGateway
{
    public $config;
    public $request;
    public $merchant;

    public function __construct($request)
    {

        $this->config   = [
            'merchant_id' => env('WOYWO_MERCHANT_ID',null),
            'key'     => env('WOYWO_KEY',null),
        ];
        $request_body  = file_get_contents('php://input');
        $this->request  = json_decode($request_body, true);
        $this->merchant = new Merchant($this->config);
    }
    public function run(){
        try {
            switch ($this->request['billType']) {
                case 'CHECK':
                    $this->CheckTransaction();
                    break;
                case 'PAY':
                    $this->PayTransaction();
                    break;
                default:
                    throw new WoywoException(WoywoException::ERROR_METHOD_NOT_FOUND);

            }
        }catch (WoywoException $exception){
            $exception->send();
        }
    }
    public function CheckTransaction(){
        if (!isset($this->request['order_id'])|| !isset($this->request['amount']))
            throw new WoywoException(WoywoException::ERROR_INSUFFICIENT_PRIVILEGE);

        $order = Order::find($this->request['order_id']);
        if ($order) {
            if (!$order)
                throw new WoywoException(WoywoException::ERROR_ORDER_NOT_FOUND);
            if ($order->state == Order::STATE_PAY_ACCEPTED)
                throw new WoywoException(WoywoException::ERROR_ALREADY_PAID);
            if ($order->state != Order::STATE_AVAILABLE)
                throw new WoywoException(WoywoException::ERROR_ORDER_NOT_AVAILABLE);
            if ($order->price != $this->request['amount'])
                throw new WoywoException(WoywoException::ERROR_INVALID_AMOUNT);
        }
        throw new WoywoException(WoywoException::SUCCESS);
    }
    public function PayTransaction(){
        try {
            $this->CheckTransaction();
        } catch (WoywoException $e) {
            if ($e->status != WoywoException::SUCCESS)
                $e->send();
        }
        if (is_null($this->request['mac']) || is_null($this->request['tran_date'])
            || is_null($this->request['tran_id']))
            throw new WoywoException(WoywoException::ERROR_INSUFFICIENT_PRIVILEGE);

        $mac = $this->getMAC();
        if ($this->request['mac'] != $mac)
            throw new WoywoException(WoywoException::ERROR_INSUFFICIENT_PRIVILEGE);

        $create_time                        = Format::timestamp(true);
        $transaction                        = new Transaction();
        $transaction->payment_system        = Transaction::WOYWO;
        $transaction->system_transaction_id = $this->request['tran_id'];
        $transaction->system_time           = $this->request['tran_date'];
        $transaction->system_time_datetime  = Format::timestamp2datetime($this->request['tran_date']);
        $transaction->create_time           = Format::timestamp2datetime($create_time);
        $transaction->state                 = Transaction::STATE_COMPLETED;
        $transaction->amount                = $this->request['amount'];
        $transaction->currency_code         = Transaction::CODE_UZS;
        $transaction->order_id              = $this->request['order_id'];
        $transaction->save(); // after save $transaction->id will be populated with the newly created transaction's id.

        $order = Order::find($this->request['order_id']);
        $str = "Pul o'tkazishi  muvofaqqiyatli yakunlandi ✅";
        if ($order) {
            $order->changeState(Order::STATE_PAY_ACCEPTED);
            $str = $str
                . "\n<b>Buyurtma raqami 📥</b>: " . $order->id
                . "\n<b>Sug'urtalanuvchi 👥</b>: " . $order->user->detail->name
                . " \n<b>Summasi 💰:</b>" . $order->price . " sum ."
                . "\n<b>To'lov tizimi:</b> Uzcard";
        }else{
            $str = $str
                . "\n<b>Buyurtma raqami 📥</b>: " . $this->request['order_id']
                . " \n<b>Summasi 💰:</b>" . $this->request['amount'] . " sum ."
                . "\n<b>To'lov tizimi:</b> Uzcard";;
        }
        TelegramController::send($str);
        // $temp = cURL::send_result($transaction);

        throw new WoywoException(WoywoException::SUCCESS);

    }
    public static function getRedirectParams($order)
    {
        $hash = base64_encode('m='.env('WOYWO_MERCHANT_ID').';ac.order_id='.$order->id.';a='.$order->price.';r='.urlencode(route('home')));
        return [
            'base64' => $hash
        ];
    }
    public function getMAC() {
        $str = $this->config['key'] . $this->config['merchant_id'] .
                $this->request['order_id'] . $this->request['tran_date'] . $this->request['amount'];
        return sha1($str);
    }
}
