<?php

namespace Goodoneuz\PayUz\Bnpl\Contracts;

use Goodoneuz\PayUz\Bnpl\ValueObjects\Contract;
use Goodoneuz\PayUz\Bnpl\ValueObjects\Eligibility;
use Goodoneuz\PayUz\Bnpl\ValueObjects\ContractResult;
use Goodoneuz\PayUz\Bnpl\ValueObjects\ContractStatus;

/**
 * A Buy-Now-Pay-Later / installments gateway (Uzum Nasiya is the first driver).
 *
 * This is a credit-contract lifecycle, not card acquiring: check the buyer's
 * eligibility, calculate the installment tariffs, create a contract, then the
 * buyer signs/activates it (in the gateway's WebView), and you confirm/cancel and
 * poll status. There is no immediate settlement and no signed webhook — state is
 * obtained by {@see status()}.
 *
 * Money is in tiyin at this boundary (consistent with the rest of the package);
 * the driver converts to the gateway's unit on the wire. Items are arrays whose
 * `price` is in tiyin — the driver converts and forwards the gateway's other keys
 * (product_id / amount / name / category / unit_id / imei). Methods throw a
 * {@see \Goodoneuz\PayUz\Bnpl\Exceptions\BnplException} on transport/auth faults;
 * confirm()/cancel() return a {@see ContractResult} carrying the business code.
 */
interface BnplDriver
{
    /**
     * Check whether a phone's owner can use installments.
     *
     * @param string $phone e.g. "998901234567"
     * @return Eligibility
     */
    public function checkEligibility($phone);

    /**
     * Calculate the available installment tariffs for a basket.
     *
     * @param int   $buyerId from {@see Eligibility::buyerId()}
     * @param array $items   [['product_id'=>.., 'price'=>tiyin, 'amount'=>qty], …]
     * @return \Goodoneuz\PayUz\Bnpl\ValueObjects\InstallmentPlan[]
     */
    public function calculate($buyerId, array $items);

    /**
     * Create an installment contract for a chosen tariff.
     *
     * @param int         $buyerId
     * @param string      $period     the chosen tariff id (from a plan)
     * @param array       $items      [['name'=>.., 'price'=>tiyin, 'amount'=>qty, 'category'=>.., 'unit_id'=>..], …]
     * @param string|null $extOrderId your merchant order id
     * @param string|null $returnUrl  browser return after signing
     * @return Contract
     */
    public function createContract($buyerId, $period, array $items, $extOrderId = null, $returnUrl = null);

    /**
     * Confirm (finalize) a contract.
     *
     * @param int $contractId {@see Contract::contractId()}
     * @return ContractResult
     */
    public function confirm($contractId);

    /**
     * Cancel a contract (full only — Nasiya has no partial refund).
     *
     * @param int $orderId {@see Contract::orderId()} — NOT the contract id
     * @return ContractResult
     */
    public function cancel($orderId);

    /**
     * Poll a contract's current state.
     *
     * @param int $contractId {@see Contract::contractId()}
     * @return ContractStatus
     */
    public function status($contractId);

    /**
     * @return string driver name (e.g. 'uzum_nasiya', 'null')
     */
    public function name();
}
