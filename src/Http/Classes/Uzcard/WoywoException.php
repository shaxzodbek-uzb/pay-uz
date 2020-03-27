<?php
/**
 * Created by PhpStorm.
 * User: Shaxzodbek
 * Date: 24.05.2018
 * Time: 15:37
 */

namespace App\Http\Classes\Woywo;


class WoywoException extends \Exception
{
    const SUCCESS                       = 0;
    const ERROR_INSUFFICIENT_PRIVILEGE  = -1;
    const ERROR_INVALID_AMOUNT          = -2;
    const ERROR_ALREADY_PAID            = -3;
    const ERROR_TRANSACTION_NOT_FOUND   = -4;
    const ERROR_INTERNAL_SYSTEM         = -5;
    const ERROR_UNKNOWN                 = -6;
    const ERROR_INVALID_JSON_RPC_OBJECT = -7;
    const ERROR_METHOD_NOT_FOUND        = -8;
    const ERROR_ORDER_NOT_FOUND         = -9;
    const ERROR_ORDER_NOT_AVAILABLE     = -10;

    public $message;
    public $status;

    /**
     * PaycomException constructor.
     * @param $status
     */
    public function __construct($status)
    {
        $this->status = $status;

        switch ($status){
            case 0: $this->message='Success'; break;
            case -1: $this->message = 'Sign check failed'; break;
            case -2: $this->message = 'Incorrect parameter amount'; break;
            case -3: $this->message = 'Transaction already paid'; break;
            case -4: $this->message = 'Transaction does not exist'; break;
            case -5: $this->message = 'System Error'; break;
            default:
                    $this->message = 'Unknown Error'; break;
        }
    }

    public function send()
    {
        header('Content-Type: application/json; charset=UTF-8');
        // create response
        $response = array();
        $response['status']  = $this->status;
        $response['message'] = $this->message;
        echo json_encode($response);
        exit();
    }

}
