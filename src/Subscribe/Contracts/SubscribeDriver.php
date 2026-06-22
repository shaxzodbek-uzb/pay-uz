<?php

namespace Goodoneuz\PayUz\Subscribe\Contracts;

use Goodoneuz\PayUz\Subscribe\Card;
use Goodoneuz\PayUz\Subscribe\Charge;
use Goodoneuz\PayUz\Subscribe\VerifyCode;

/**
 * A card-tokenization + recurring-charge gateway (Payme Subscribe is the first
 * driver; Click card_token / Octo bind can follow).
 *
 * Lifecycle: createCard → sendVerifyCode → verifyCard (token becomes chargeable)
 * → createReceipt → payReceipt. For two-stage payments, createReceipt/payReceipt
 * run in hold mode and confirmHold captures.
 *
 * On failure these methods THROW a
 * {@see \Goodoneuz\PayUz\Subscribe\Exceptions\SubscribeException} (card ops are
 * interactive — a decline must surface), and return a value object on success.
 * All amounts are in tiyin (1 som = 100 tiyin); persist only the card token,
 * never the PAN.
 */
interface SubscribeDriver
{
    // --- card tokenization ---

    /**
     * Mint a card token. With $save=true the token is reusable (recurrent).
     *
     * @param string $number full PAN
     * @param string $expire "MMYY"
     * @param bool   $save
     * @param array  $account optional merchant account schema
     * @return Card unverified until {@see verifyCard()}
     */
    public function createCard($number, $expire, $save = true, array $account = []);

    /**
     * Send the OTP for a token.
     *
     * @param string $token
     * @return VerifyCode
     */
    public function sendVerifyCode($token);

    /**
     * Confirm the OTP; the returned card is verified (chargeable).
     *
     * @param string $token
     * @param string $code
     * @return Card
     */
    public function verifyCard($token, $code);

    /**
     * Validate a stored token without charging it.
     *
     * @param string $token
     * @return Card
     */
    public function checkCard($token);

    /**
     * Delete a stored token.
     *
     * @param string $token
     * @return bool
     */
    public function removeCard($token);

    // --- receipts / charges ---

    /**
     * Create an unpaid receipt for an amount.
     *
     * @param int    $amount  tiyin
     * @param array  $account merchant account keys (e.g. ['order_id' => 42])
     * @param array  $options 'description', 'detail', 'hold' => true
     * @return Charge state 0 (created)
     */
    public function createReceipt($amount, array $account, array $options = []);

    /**
     * Pay a receipt with a verified token.
     *
     * @param string $receiptId
     * @param string $token
     * @param array  $options 'payer' => [...], 'hold' => true
     * @return Charge state 4 (paid) or 5 (held)
     */
    public function payReceipt($receiptId, $token, array $options = []);

    /**
     * Cancel a receipt (also the void/release path for a hold).
     *
     * @param string $receiptId
     * @return Charge
     */
    public function cancelReceipt($receiptId);

    /**
     * Lightweight state poll.
     *
     * @param string $receiptId
     * @return int receipt state code
     */
    public function checkReceipt($receiptId);

    /**
     * Fetch the full receipt.
     *
     * @param string $receiptId
     * @return Charge
     */
    public function getReceipt($receiptId);

    // --- holds (two-stage) ---

    /**
     * Capture a held receipt (state 5 → 4).
     *
     * @param string $receiptId
     * @return Charge
     */
    public function confirmHold($receiptId);

    /**
     * @return string driver name (e.g. 'payme', 'null')
     */
    public function name();
}
