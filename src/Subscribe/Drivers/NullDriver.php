<?php

namespace Goodoneuz\PayUz\Subscribe\Drivers;

use Goodoneuz\PayUz\Subscribe\Card;
use Goodoneuz\PayUz\Subscribe\Charge;
use Goodoneuz\PayUz\Subscribe\VerifyCode;
use Goodoneuz\PayUz\Subscribe\Contracts\SubscribeDriver;

/**
 * A no-op Subscribe driver — the safe default on a fresh install.
 *
 * It simulates the happy path without contacting any gateway: cards mint a
 * synthetic token, OTP "succeeds", and charges come back paid. Tokens it issues
 * carry no PAN. Use it for local development and tests, then switch
 * `subscribe.default` to a real driver in production.
 */
class NullDriver implements SubscribeDriver
{
    public function createCard($number, $expire, $save = true, array $account = [])
    {
        return new Card([
            'number'    => $this->mask($number),
            'expire'    => $this->renderExpire($expire),
            'token'     => $this->fakeToken($number, $expire),
            'recurrent' => (bool) $save,
            'verify'    => false, // not yet OTP-verified
        ]);
    }

    public function sendVerifyCode($token)
    {
        return new VerifyCode(['sent' => true, 'phone' => '998*****', 'wait' => 60000]);
    }

    public function verifyCard($token, $code)
    {
        return new Card([
            'number'    => '8600********0000',
            'expire'    => '03/99',
            'token'     => (string) $token,
            'recurrent' => true,
            'verify'    => true,
        ]);
    }

    public function checkCard($token)
    {
        return new Card([
            'number'    => '8600********0000',
            'expire'    => '03/99',
            'token'     => (string) $token,
            'recurrent' => true,
            'verify'    => true,
        ]);
    }

    public function removeCard($token)
    {
        return true;
    }

    public function createReceipt($amount, array $account, array $options = [])
    {
        return new Charge([
            '_id'    => $this->fakeReceiptId($account),
            'state'  => Charge::STATE_CREATED,
            'amount' => (int) $amount,
        ]);
    }

    public function payReceipt($receiptId, $token, array $options = [])
    {
        $state = !empty($options['hold']) ? Charge::STATE_HELD : Charge::STATE_PAID;

        return new Charge([
            '_id'   => (string) $receiptId,
            'state' => $state,
            'card'  => ['number' => '8600********0000'],
        ]);
    }

    public function cancelReceipt($receiptId)
    {
        return new Charge(['_id' => (string) $receiptId, 'state' => Charge::STATE_CANCELLED]);
    }

    public function checkReceipt($receiptId)
    {
        return Charge::STATE_PAID;
    }

    public function getReceipt($receiptId)
    {
        return new Charge(['_id' => (string) $receiptId, 'state' => Charge::STATE_PAID]);
    }

    public function confirmHold($receiptId)
    {
        return new Charge(['_id' => (string) $receiptId, 'state' => Charge::STATE_PAID]);
    }

    public function name()
    {
        return 'null';
    }

    // --- helpers ---

    private function mask($number)
    {
        $number = preg_replace('/\D+/', '', (string) $number);
        if (strlen($number) < 10) {
            return '8600********0000';
        }

        return substr($number, 0, 4).str_repeat('*', strlen($number) - 8).substr($number, -4);
    }

    private function renderExpire($expire)
    {
        $expire = preg_replace('/\D+/', '', (string) $expire);

        return strlen($expire) === 4 ? substr($expire, 0, 2).'/'.substr($expire, 2, 2) : (string) $expire;
    }

    private function fakeToken($number, $expire)
    {
        return 'null_'.substr(md5($number.'|'.$expire), 0, 24);
    }

    private function fakeReceiptId(array $account)
    {
        return 'null'.substr(md5(json_encode($account)), 0, 20);
    }
}
