<?php
/**
 * Created by PhpStorm.
 * User: Azizbek Eshonaliyev
 * Date: 2/22/2019
 * Time: 8:26 PM
 */

namespace Goodoneuz\PayUz\Services;


use Goodoneuz\PayUz\Models\Transaction;

class TransactionService
{

    /**
     * @param $transaction_id
     * @return mixed
     */
    public static function getTransactionById($transaction_id)
    {
        return Transaction::find($transaction_id);
    }
}
