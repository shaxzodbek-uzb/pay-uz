<?php

namespace Goodoneuz\PayUz\Http\Classes\Click;

use Goodoneuz\PayUz\Models\PaymentSystem;
use Goodoneuz\PayUz\Services\PaymentService;
use Goodoneuz\PayUz\Http\Classes\PaymentException;
use Goodoneuz\PayUz\Services\PaymentSystemService;

class Response{
    
    const SUCCESS                       = 0;
    const ERROR_SIGN_CHECK              = -1;
    const ERROR_INVALID_AMOUNT          = -2;
    const ERROR_ACTION_NOT_FOUND        = -3;
    const ERROR_ALREADY_PAID            = -4;
    const ERROR_ORDER_NOT_FOUND         = -5;
    const ERROR_TRANSACTION_NOT_FOUND   = -6;
    const ERROR_UPDATE_ORDER            = -7;
    const ERROR_REQUEST_FROM            = -8;
    const ERROR_TRANSACTION_CANCELLED   = -9;
    const ERROR_VENDOR_NOT_FOUND         = -10;
    public $result = [];

    /**
     * @param null $status
     * @param null $params
     * @throws PaymentException
     */
    public function setResult($status = null, $params = null)
    {
        $this->result['error'] = $status;
        switch ($status) {
            case self::SUCCESS:
                $this->result['error_note'] = 'Success';
                break;
            case self::ERROR_SIGN_CHECK:
                $this->result['error_note'] = 'Ошибка проверки подписи';
                break;
            case self::ERROR_INVALID_AMOUNT:
                $this->result['error_note'] = 'Неверная сумма оплаты';
                break;
            case self::ERROR_ACTION_NOT_FOUND:
                $this->result['error_note'] = 'Запрашиваемое действие не найдено';
                break;
            case self::ERROR_ALREADY_PAID:
                $this->result['error_note'] = 'Транзакция ранее была подтверждена (при попытке подтвердить или отменить ранее подтвержденную транзакцию)';
                break;
            case self::ERROR_ORDER_NOT_FOUND:
                $this->result['error_note'] = 'Не найдет пользователь/заказ (проверка параметра merchant_trans_id)';
                break;
            case self::ERROR_TRANSACTION_NOT_FOUND:
                $this->result['error_note'] = 'Не найдена транзакция (проверка параметра merchant_prepare_id)';
                break;
            case self::ERROR_UPDATE_ORDER:
                $this->result['error_note'] = 'Ошибка при изменении данных пользователя (изменение баланса счета и т.п.)';
                break;
            case self::ERROR_REQUEST_FROM:
                $this->result['error_note'] = 'Ошибка в запросе от CLICK (переданы не все параметры и т.п.)';
                break;
            case self::ERROR_TRANSACTION_CANCELLED:
                $this->result['error_note'] = 'Транзакция ранее была отменена (При попытке подтвердить или отменить ранее отмененную транзакцию)';
                break;
            default:
                $this->result['error_note'] = 'ERROR_VENDOR_NOT_FOUND';
                break;
        }
        if (is_array($params)){
            foreach ($params as $key => $param ){
                $this->result[$key] = $param;
            }
        }
        throw new PaymentException($this);
    }

    /**
     *
     */
    public function send(){
        $params = PaymentSystemService::getPaymentSystemParamsCollect(PaymentSystem::CLICK);
        $timestamp = time();
        $digest = sha1($timestamp .  $params['secret_key']);
        
        if(env('APP_ENV') != 'testing')
            header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($this->result);
    }
}
