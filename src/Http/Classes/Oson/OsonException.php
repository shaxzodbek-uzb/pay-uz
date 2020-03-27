<?php
/**
 * Created by PhpStorm.
 * User: Shaxzodbek
 * Date: 25.05.2018
 * Time: 9:57
 */

namespace App\Http\Classes\Oson;


use Illuminate\Support\Facades\Log;

class OsonException extends \Exception
{
    const SUCCESS                       = 0;
    const ERROR_AUTHORIZATION           = 1;
    const ERROR_INVALID_AMOUNT          = 2;
    const ERROR_ALREADY_PAID            = 3;
    const ERROR_TRANSACTION_NOT_FOUND   = 4;
    const ERROR_ORDER_NOT_FOUND         = 5;
    const ERROR_INTERNAL_SYSTEM         = 10;
    const ERROR_UNKNOWN                 = 11;
    const ERROR_ORDER_NOT_AVAILABLE     = 12;

    public $result;
    public $status;

    /**
     * PaycomException constructor.
     * @param $status
     * @param $params ['providerTrnId','ts'] [exist]
     */
    public function __construct($status,$params)
    {

        $this->result = $params;
        $this->result['status'] = $status;
        switch ($status){
            case 0: $this->message='Нет ошибок'; break;
            case 1: $this->message = 'Ошибка авторизации'; break;
            case 2: $this->message = 'Неверный параметр'; break;
            case 3: $this->message = 'Транзакция уже существует'; break;
            case 4: $this->message = 'Транзакция уже отменена'; break;
            case 5: $this->message = 'Клиент не найден'; break;
            case 10: $this->message = 'Системная ошибка'; break;
            case 11: $this->message = 'Неизвестная ошибка'; break;
            default: $this->status = 12;
                $this->message = 'Услуга временно не поддерживается'; break;
        }
    }

    public function send()
    {
        header('Content-Type: application/json; charset=UTF-8');
        // create response
        $response = array();
        $response[]  = [
            'jsonrcp'=> '2.0',
            'result' => $this->result
        ];
        echo json_encode($response);
        exit();
    }
}
