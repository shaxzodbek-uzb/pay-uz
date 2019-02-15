<?php
namespace Goodoneuz\PayUz\Http\Classes\Payme;

use App;
use Goodoneuz\PayUz\Http\Classes\PaymentException;
use Goodoneuz\PayUz\Http\Classes\DataFormat;
use App\Transaction;

class Payme {
    public $config;
    public $request;
    public $response;
    public $merchant;

    
    public function __construct()
    {
        $this->config   = config('payment.payme');
        $this->response = new Response();
        
        $this->request  = new Request($this->response);
        $this->response->setRequest($this->request);
        $this->merchant = new Merchant($this->config, $this->response);

    }
    public function run()
    {

        // authorize session
        $this->merchant->Authorize();

        // handle request
        switch ($this->request->method) {
            case 'CheckPerformTransaction':
                $this->CheckPerformTransaction();
                break;
            case 'CheckTransaction':
                $this->CheckTransaction();
                break;
            case 'CreateTransaction':
                $this->CreateTransaction();
                break;
            case 'PerformTransaction':
                $this->PerformTransaction();
                break;
            case 'CancelTransaction':
                $this->CancelTransaction();
                break;
            case 'ChangePassword':
                $this->ChangePassword();
                break;
            case 'GetStatement':
                $this->GetStatement();
                break;
            default:
                $this->response->error(
                    Response::ERROR_METHOD_NOT_FOUND,
                    'Method not found.',
                    $this->request->method
                );
                break;
        }
    }
    private function CheckTransaction()
    {
        $found  =  $this->findTransactionByParams($this->request->params);
        if (!$found) {
            $this->response->error(
                Response::ERROR_TRANSACTION_NOT_FOUND,
                'Transaction not found.'
            );
        }

        $this->response->success([
            'create_time'  => 1*$found->create_time,
            'perform_time' => 1*$found->perform_time,
            'cancel_time'  => 1*$found->cancel_time,
            'transaction'  => (string)$found->id,
            'state'        => 1*$found->state,
            'reason'       => ($found->comment && is_numeric($found->comment)) ? 1 * $found->comment : null,
        ]);
    }
    private function CheckPerformTransaction()
    {
        $this->validateParams($this->request->params);

        $order = $this->findOrderByParams($this->request->params['account']);
        if (!$order->isPayable($this->request->params['amount'])){
            $this->response->error(
                Response::ERROR_COULD_NOT_PERFORM,
                'There is other active/completed transaction for this order.'
            );
        }
        

        // if control is here, then we pass all validations and checks
        // send response, that order is ready to be paid.
        $this->response->success(['allow' => true]);
    }

    private function findOrderByParams($account)
    {
        $order = false;
        // Example implementation to load order by id
       if (isset($account['order_id'])) {
           $order = Order::where('id',$account['order_id'])->first();
           if ($order)
               return $order;
       }

       if (!$order) {
           $this->response->error(
            Response::ERROR_INVALID_ACCOUNT,
            Response::message( 'Buyurtma topilmadi.', 'Buyurtma topilmadi.', 'Buyurtma topilmadi.'),
               'order_id'
           );
       }
    }
    public function validateParams(array $params)
    {
        // for example, check amount is numeric
        if (!is_numeric($params['amount'])) {
            $this->response->error( Response::ERROR_INVALID_AMOUNT, 'Incorrect amount.');
        }

        // assume, we should have order_id
        if (!isset($params['account']['order_id'])) {
            $this->response->error(
                Response::ERROR_INVALID_ACCOUNT,
                Response::message( 'Неверный код заказа.', 'Harid kodida xatolik.', 'Incorrect order code.'),
                'order_id'
            );
        }

        return true;
    }
    private function CreateTransaction()
    {

        $order = $this->findOrderByParams($this->request->params['account']);
        $this->validateParams($this->request->params);


//        // todo: Check, is there any other transaction for this order/service
//        $transaction = $this->findTransactionByParams(['account' => $this->request->params['account']]);
//        if ($transaction) {
//            if (($transaction->state == Transaction::STATE_CREATED || $transaction->state == Transaction::STATE_COMPLETED)
//                && $transaction->system_transaction_id !== $this->request->params['id']) {
//                $this->response->error(
//                    Response::ERROR_INVALID_ACCOUNT,
//                    'There is other active/completed transaction for this order.'
//                );
//            }
//        }

        $transaction = $this->findTransactionByParams($this->request->params);
        if ($transaction) {
            if ($transaction->state != Transaction::STATE_CREATED) { // validate transaction state
                $this->response->error(
                    Response::ERROR_COULD_NOT_PERFORM,
                    'Transaction found, but is not active.'
                );
            } elseif ($transaction->isExpired()) { // if transaction timed out, cancel it and send error
                $transaction->cancel(Transaction::REASON_CANCELLED_BY_TIMEOUT);
                $this->response->error(
                    Response::ERROR_COULD_NOT_PERFORM,
                    'Transaction is expired.'
                );
            } else { // if transaction found and active, send it as response
                $this->response->success([
                    'create_time' => 1*$transaction->create_time,
                    'transaction' => (string)$transaction->system_transaction_id,
                    'state'       => 1*$transaction->state,
                    'receivers'   => $transaction->receivers,
                ]);
            }
        } else { // transaction not found, create new one

            // validate new transaction time
            if (DataFormat::timestamp2milliseconds(1 * $this->request->params['time']) - DataFormat::timestamp(true) >= Transaction::TIMEOUT) {
                $this->response->error(
                    Response::ERROR_INVALID_ACCOUNT,
                    Response::message(
                        'С даты создания транзакции прошло ' . Transaction::TIMEOUT . 'мс',
                        'Tranzaksiya yaratilgan sanadan ' . Transaction::TIMEOUT . 'ms o`tgan',
                        'Since create time of the transaction passed ' . Transaction::TIMEOUT . 'ms'
                    ),
                    'time'
                );
            }

            // create new transaction
            // keep create_time as timestamp, it is necessary in response
            $create_time = DataFormat::timestamp(true);
            $transaction = Transaction::create([
                'payment_system'        => Transaction::PAYME,
                'system_transaction_id' => $this->request->params['id'],
                'amount'                =>1*($this->request->amount / 100),
                'currency_code'         => Transaction::CURRENCY_CODE_UZS,
                'payable_type'          => 'App\Order', 
                'payable_id'            => $this->request->account('order_id'),
                'state'                 => Transaction::STATE_CREATED,
                'create_time'           => 1*$create_time, 
                'system_time_datetime'  => DataFormat::timestamp2datetime($this->request->params['time']),
                'comment'               => (isset($this->request->params['error_note'])?$this->request->params['error_note']:''),
            ]);

            // send response
            $this->response->success([
                'create_time' => 1*$create_time,
                'transaction' => (string)$transaction->system_transaction_id,
                'state'       => 1*$transaction->state,
                'receivers'   => null,
            ]);
        }
    }

    public function findTransactionByParams($params)
    {
        $transaction = Transaction::where('payment_system',Transaction::PAYME)->where('system_transaction_id',$params['id'])->first();
        return $transaction;  
    }
    private function PerformTransaction()
    {
        $transaction = $this->findTransactionByParams($this->request->params);

        // if transaction not found, send error
        if (!$transaction) {
            $this->response->error(Response::ERROR_TRANSACTION_NOT_FOUND, 'Transaction not found.');
        }

        switch ($transaction->state) {
            case Transaction::STATE_CREATED: // handle active transaction
                if ($transaction->isExpired()) { // if transaction is expired, then cancel it and send error
                    $transaction->cancel(Transaction::REASON_CANCELLED_BY_TIMEOUT);
                    $this->response->error(
                        Response::ERROR_COULD_NOT_PERFORM,
                        'Transaction is expired.'
                    );
                } else { // perform active transaction

                    //todo taobao saytiga jo'natish
                    $perform_time               = DataFormat::timestamp(true);
                    $transaction->state         = Transaction::STATE_COMPLETED;
                    $transaction->perform_time  = $perform_time;
                    $transaction->save();
                    $order = Order::find($transaction->payable_id);
                    $order->pay($transaction->id);
                    $this->response->success([
                        'transaction'  => (string)$transaction->system_transaction_id,
                        'perform_time' => 1*$perform_time,
                        'state'        => 1*$transaction->state,
                    ]);
                }
                break;

            case Transaction::STATE_COMPLETED: // handle complete transaction
                $this->response->success([
                    'transaction'  => (string)$transaction->system_transaction_id,
                    'perform_time' => 1*$transaction->perform_time,
                    'state'        => 1*$transaction->state,
                ]);
                $order = Order::find($transaction->payable_id);
                $order->pay($transaction->id);
                break;

            default:
                // unknown situation
                $this->response->error(
                    Response::ERROR_COULD_NOT_PERFORM,
                    'Could not perform this operation.'
                );
                break;
        }
    }
    private function CancelTransaction()
    {
        $transaction = $this->findTransactionByParams($this->request->params);

        // if transaction not found, send error
        if (!$transaction) {
            $this->response->error(Response::ERROR_TRANSACTION_NOT_FOUND, 'Transaction not found.');
        }

        switch ($transaction->state) {
            // if already cancelled, just send it
            case Transaction::STATE_CANCELLED:
            case Transaction::STATE_CANCELLED_AFTER_COMPLETE:
                $this->response->success([
                    'transaction' => (string)$transaction->id,
                    'cancel_time' => 1*$transaction->cancel_time,
                    'state'       => 1*$transaction->state,
                ]);
                break;

            // cancel active transaction
            case Transaction::STATE_CREATED:
                // cancel transaction with given reason
                $transaction->cancel(1 * $this->request->params['reason']);
                // after $found->cancel(), cancel_time and state properties populated with data


                $cancel_time               = DataFormat::timestamp(true);
                $transaction->cancel_time  = $cancel_time;
                $transaction->save();

                // change order state to cancelled

                // send response
                $this->response->success([
                    'transaction' => (string)$transaction->id,
                    'cancel_time' => 1*$cancel_time,
                    'state'       => 1*$transaction->state,
                ]);
                break;

            case Transaction::STATE_COMPLETED:
                // find order and check, whether cancelling is possible this order
                if (true) {
                    // cancel and change state to cancelled
                    $transaction->cancel(1 * $this->request->params['reason']);
                    // after $found->cancel(), cancel_time and state properties populated with data

                    // send response
                    $this->response->success([
                        'transaction' => (string)$transaction->id,
                        'cancel_time' => 1*$transaction->cancel_time,
                        'state'       => 1*$transaction->state,
                    ]);
                } else {
                    $this->response->error(
                        Response::ERROR_COULD_NOT_CANCEL,
                        'Could not cancel transaction. Order is delivered/Service is completed.'
                    );
                }
                break;
        }
    }
    private function ChangePassword()
    {
        // validate, password is specified, otherwise send error
        if (!isset($this->request->params['password']) || !trim($this->request->params['password'])) {
            $this->response->error(Response::ERROR_INVALID_ACCOUNT, 'New password not specified.', 'password');
        }

        // if current password specified as new, then send error
        if ($this->merchant->config['password'] == $this->request->params['password']) {
            $this->response->error(Response::ERROR_INSUFFICIENT_PRIVILEGE, 'Insufficient privilege. Incorrect new password.');
        }

        $path = base_path('.env');
        $completed = false;
        if (file_exists($path)) {
            if(file_put_contents($path, str_replace(
                'PAYME_PASSWORD='.$this->config['password'], 'PAYME_PASSWORD='.$this->request->params['password'], file_get_contents($path)
            )))
                $completed = true;
        }
        // example implementation, that saves new password into file specified in the configuration
        if (!$completed) {
            $this->response->error(Response::ERROR_INTERNAL_SYSTEM, 'Internal System Error.');
        }

        // if control is here, then password is saved into data store
        // send success response
        $this->response->success(['success' => true]);
    }

    private function GetStatement()
    {
        // validate 'from'
        if (!isset($this->request->params['from'])) {
            $this->response->error(Response::ERROR_INVALID_ACCOUNT, 'Incorrect period.', 'from');
        }

        // validate 'to'
        if (!isset($this->request->params['to'])) {
            $this->response->error(Response::ERROR_INVALID_ACCOUNT, 'Incorrect period.', 'to');
        }

        // validate period
        if (1 * $this->request->params['from'] >= 1 * $this->request->params['to']) {
            $this->response->error(Response::ERROR_INVALID_ACCOUNT, 'Incorrect period. (from >= to)', 'from');
        }

        // get list of transactions for specified period
        $transactions  = $this->getReport($this->request->params['from'], $this->request->params['to']);

        // send results back
        $this->response->success(['transactions' => $transactions]);
    }
    public function getReport($from_date, $to_date)
    {
        $from_date = DataFormat::timestamp2datetime($from_date);
        $to_date   = DataFormat::timestamp2datetime($to_date);

        $transactions = Transaction::where('payment_system',Transaction::PAYME)
            ->where('state',Transaction::STATE_COMPLETED)
            ->where('system_time_datetime','>=',$from_date)
            ->where('system_time_datetime','<=',$to_date)->get();
        // assume, here we have $rows variable that is populated with transactions from data store
        // normalize data for response
        $result = [];
        foreach ($transactions as $row) {
            $result[] = [
                'id'           => $row['system_transaction_id'], // paycom transaction id
                'time'         => 1 * $row['system_time'], // paycom transaction timestamp as is
                'amount'       => 1 * $row['amount'],
                'account'      => [
                    'user_key' => 1 * $row['user_key'], // account parameters to identify client/order/service
                    // ... additional parameters may be listed here, which are belongs to the account
                ],
                'create_time'  => DataFormat::datetime2timestamp($row['create_time']),
                'perform_time' => DataFormat::datetime2timestamp($row['perform_time']),
                'cancel_time'  => DataFormat::datetime2timestamp($row['cancel_time']),
                'transaction'  => (string)(1 * $row['id']),
                'state'        => 1 * $row['state'],
                'reason'       => isset($row['comment']) ? 1 * $row['comment'] : null,
                'receivers'    => isset($row['receivers']) ? json_decode($row['receivers'], true) : null,
            ];
        }
        return $result;

    }
    public static function getRedirectParams($pay){
        return [
            'merchant' => env('PAYME_MERCHANT_ID',null),
            'amount' => $pay->amount*100,
            'account[order_id]' => $pay->order_id,
            'lang' => 'ru',
            'currency' => Transaction::CURRENCY_CODE_UZS,
            'callback' => 'http://themall.uz',
            'callback_timeout' => 20000,
        ];
    }
}
