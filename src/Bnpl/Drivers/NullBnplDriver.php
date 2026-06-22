<?php

namespace Goodoneuz\PayUz\Bnpl\Drivers;

use Goodoneuz\PayUz\Bnpl\ValueObjects\Contract;
use Goodoneuz\PayUz\Bnpl\ValueObjects\Eligibility;
use Goodoneuz\PayUz\Bnpl\Contracts\BnplDriver;
use Goodoneuz\PayUz\Bnpl\ValueObjects\ContractResult;
use Goodoneuz\PayUz\Bnpl\ValueObjects\ContractStatus;
use Goodoneuz\PayUz\Bnpl\ValueObjects\InstallmentPlan;

/**
 * A no-op BNPL driver — the safe default on a fresh install. It simulates the
 * installment happy path (buyer verified, one 12-month plan, contract created +
 * confirmable) without contacting Uzum. Use it for local development and tests,
 * then switch `bnpl.default` to 'uzum_nasiya' in production.
 */
class NullBnplDriver implements BnplDriver
{
    public function checkEligibility($phone)
    {
        return new Eligibility([
            'status'   => Eligibility::STATUS_VERIFIED,
            'buyer_id' => 1,
            'webview'  => 'https://null.nasiya/register',
        ]);
    }

    public function calculate($buyerId, array $items)
    {
        $total = 0;
        foreach ($items as $item) {
            $price = isset($item['price']) ? (int) $item['price'] : 0;
            $qty   = isset($item['amount']) ? (int) $item['amount'] : 1;
            $total += $price * $qty;
        }

        return [new InstallmentPlan([
            'tariff_id'     => '12 Default',
            'period_months' => 12,
            'total'         => $total,
            'origin'        => $total,
            'monthly'       => $total > 0 ? (int) round($total / 12) : 0,
            'deposit'       => 0,
            'is_available'  => true,
        ])];
    }

    public function createContract($buyerId, $period, array $items, $extOrderId = null, $returnUrl = null)
    {
        return new Contract([
            'contract_id'  => 1,
            'order_id'     => 1,
            'webview_path' => 'https://null.nasiya/sign/1',
        ]);
    }

    public function confirm($contractId)
    {
        return new ContractResult(true, ContractResult::CODE_OK, ['act_pdf_url' => 'https://null.nasiya/act/1.pdf']);
    }

    public function cancel($orderId)
    {
        return new ContractResult(true, ContractResult::CODE_OK);
    }

    public function status($contractId)
    {
        return new ContractStatus(['contract_status' => ContractStatus::ACTIVE, 'is_signed' => true]);
    }

    public function name()
    {
        return 'null';
    }
}
