<?php

namespace Goodoneuz\PayUz\Http\Classes\Uzum;

use Goodoneuz\PayUz\Http\Classes\BaseGateway;
use Goodoneuz\PayUz\Http\Classes\DataFormat;
use Goodoneuz\PayUz\Models\PaymentSystem;
use Goodoneuz\PayUz\Models\Transaction;
use Goodoneuz\PayUz\Services\PaymentService;
use Goodoneuz\PayUz\Services\PaymentSystemService;

/**
 * Uzum Bank gateway — "Merchant API" (Model A), the server-to-server webhook
 * model where Uzum's processing centre POSTs to the merchant's endpoints.
 *
 * The five operations are exposed as separate endpoints. Wire them to a single
 * `{operation}` route, e.g.:
 *
 *   Route::post('payment/uzum/{operation}', function () {
 *       return PayUz::driver('uzum')->handle();
 *   })->where('operation', 'check|create|confirm|reverse|status');
 *
 * The operation is taken from the `operation` route parameter, falling back to
 * the last URL path segment.
 *
 * Configuration (payment_system_params for system 'uzum'):
 *   login, password   — HTTP Basic credentials issued for your terminal
 *   service_id        — your Uzum serviceId
 *   key               — which `params` field identifies the order (default 'id')
 *
 * Amounts on the wire are in tiyin (1 som = 100 tiyin); transactions are stored
 * in som, consistent with the Payme driver.
 */
class Uzum extends BaseGateway
{
    public $config;
    public $request;
    public $response;
    public $merchant;

    public function __construct()
    {
        $this->config = PaymentSystemService::getPaymentSystemParamsCollect(PaymentSystem::UZUM);
    }

    public function run()
    {
        $this->response = new Response();
        $this->request  = new Request($this->response);
        $this->merchant = new Merchant($this->config, $this->response);

        // Authenticate (Basic auth + serviceId) before doing anything else.
        $this->merchant->Authorize($this->request);

        switch ($this->operation()) {
            case 'check':
                $this->Check();
                break;
            case 'create':
                $this->Create();
                break;
            case 'confirm':
                $this->Confirm();
                break;
            case 'reverse':
                $this->Reverse();
                break;
            case 'status':
                $this->Status();
                break;
            default:
                $this->response->error(Response::ERROR_UNKNOWN_OPERATION);
        }
    }

    /**
     * Pre-validate that the account exists and is payable.
     */
    private function Check()
    {
        $model = $this->resolveModel();
        if ($model == null) {
            $this->response->error(Response::ERROR_TRANSACTION_NOT_FOUND);
        }

        PaymentService::payListener($model, null, 'before-pay');

        $this->response->success([
            'status' => Response::STATUS_OK,
            'data'   => $this->accountData($model),
        ]);
    }

    /**
     * Register / hold a transaction.
     */
    private function Create()
    {
        $model = $this->resolveModel();
        if ($model == null) {
            $this->response->error(Response::ERROR_TRANSACTION_NOT_FOUND);
        }

        if (!is_numeric($this->request->amount) ||
            !PaymentService::isProperModelAndAmount($model, $this->request->amount)) {
            $this->response->error(Response::ERROR_CHECK_PAYMENT_DATA);
        }

        $transaction = $this->findByTransId();
        if (!$transaction) {
            $create_time = DataFormat::timestamp(true);
            $detail = [
                'create_time'  => $create_time,
                'confirm_time' => null,
                'reverse_time' => null,
                'params'       => $this->request->params,
                'serviceId'    => $this->request->serviceId,
            ];

            $transaction = Transaction::create([
                'payment_system'        => PaymentSystem::UZUM,
                'system_transaction_id' => $this->request->transId,
                'amount'                => 1 * $this->request->amount / 100, // tiyin -> som
                'currency_code'         => Transaction::CURRENCY_CODE_UZS,
                'state'                 => Transaction::STATE_CREATED,
                'updated_time'          => 1 * $create_time,
                'comment'               => '',
                'detail'                => $detail,
                'transactionable_type'  => get_class($model),
                'transactionable_id'    => $model->id,
            ]);

            PaymentService::payListener($model, $transaction, 'paying');
        }

        $this->response->success([
            'transId'   => (string) $this->request->transId,
            'status'    => Response::STATUS_CREATED,
            'transTime' => 1 * $transaction->updated_time,
            'amount'    => (int) round($transaction->amount * 100),
            'data'      => $this->accountData($model),
        ]);
    }

    /**
     * Finalize a held transaction (idempotent).
     */
    private function Confirm()
    {
        $transaction = $this->findByTransId();
        if (!$transaction) {
            $this->response->error(Response::ERROR_TRANSACTION_NOT_FOUND);
        }

        if (in_array((int) $transaction->state, [
            Transaction::STATE_CANCELLED,
            Transaction::STATE_CANCELLED_AFTER_COMPLETE,
        ], true)) {
            $this->response->error(Response::ERROR_PAYMENT_CANCELLED, 400, [
                'transId' => (string) $this->request->transId,
            ]);
        }

        if ((int) $transaction->state === Transaction::STATE_CREATED) {
            $confirm_time = DataFormat::timestamp(true);

            $detail = $transaction->detail;
            $detail['confirm_time']              = $confirm_time;
            $detail['paymentSource']             = $this->request->paymentSource;
            $detail['tariff']                    = $this->request->tariff;
            $detail['processingReferenceNumber'] = $this->request->processingReferenceNumber;

            // Atomic CREATED -> COMPLETED so concurrent confirms fire after-pay once.
            $affected = Transaction::where('id', $transaction->id)
                ->where('state', Transaction::STATE_CREATED)
                ->update([
                    'state'        => Transaction::STATE_COMPLETED,
                    'updated_time' => $confirm_time,
                    'detail'       => json_encode($detail),
                ]);

            $transaction->refresh();

            if ($affected > 0) {
                PaymentService::payListener(null, $transaction, 'after-pay');
            }
        }

        $detail = $transaction->detail;
        $this->response->success([
            'transId'     => (string) $this->request->transId,
            'status'      => Response::STATUS_CONFIRMED,
            'confirmTime' => 1 * ($detail['confirm_time'] ?? $transaction->updated_time),
            'amount'      => (int) round($transaction->amount * 100),
        ]);
    }

    /**
     * Cancel / refund a transaction (idempotent).
     */
    private function Reverse()
    {
        $transaction = $this->findByTransId();
        if (!$transaction) {
            $this->response->error(Response::ERROR_TRANSACTION_NOT_FOUND);
        }

        if (in_array((int) $transaction->state, [
            Transaction::STATE_CREATED,
            Transaction::STATE_COMPLETED,
        ], true)) {
            $reverse_time = DataFormat::timestamp(true);

            // cancel() flips the state (CANCELLED / CANCELLED_AFTER_COMPLETE) and
            // stamps detail.cancel_time; we additionally record reverse_time.
            $transaction->cancel(Transaction::REASON_FUND_RETURNED);

            $detail = $transaction->detail;
            $detail['reverse_time'] = $reverse_time;
            $transaction->update([
                'updated_time' => $reverse_time,
                'detail'       => $detail,
            ]);

            PaymentService::payListener(null, $transaction, 'cancel-pay');
        }

        $detail = $transaction->detail;
        $this->response->success([
            'transId'     => (string) $this->request->transId,
            'status'      => Response::STATUS_REVERSED,
            'reverseTime' => 1 * ($detail['reverse_time'] ?? $transaction->updated_time),
            'amount'      => (int) round($transaction->amount * 100),
        ]);
    }

    /**
     * Report the current state of a transaction.
     */
    private function Status()
    {
        $transaction = $this->findByTransId();
        if (!$transaction) {
            $this->response->error(Response::ERROR_TRANSACTION_NOT_FOUND);
        }

        $detail = $transaction->detail;
        $this->response->success([
            'transId'     => (string) $this->request->transId,
            'status'      => $this->mapStatus((int) $transaction->state),
            'transTime'   => 1 * ($detail['create_time'] ?? $transaction->updated_time),
            'confirmTime' => isset($detail['confirm_time']) ? 1 * $detail['confirm_time'] : null,
            'reverseTime' => isset($detail['reverse_time']) ? 1 * $detail['reverse_time'] : null,
            'amount'      => (int) round($transaction->amount * 100),
        ]);
    }

    /**
     * Redirect-flow params. Uzum Model A is webhook-based and does not use a
     * hosted redirect; these are provided for an integrator-built checkout page
     * (mirrors the Stripe driver) and the optional Uzum deeplink (som amount).
     */
    public function getRedirectParams($model, $amount, $currency, $url)
    {
        return [
            'config'   => $this->config,
            'amount'   => $amount,
            'currency' => $currency,
            'key'      => PaymentService::convertModelToKey($model),
            'url'      => $url,
        ];
    }

    // --- helpers ---

    private function operation()
    {
        $op = request()->route('operation');
        if (!$op) {
            $segments = request()->segments();
            $op = end($segments);
        }
        return strtolower((string) $op);
    }

    private function resolveModel()
    {
        $keyName = $this->config['key'] ?? 'id';
        return PaymentService::convertKeyToModel($this->request->account($keyName));
    }

    private function findByTransId()
    {
        return Transaction::where('payment_system', PaymentSystem::UZUM)
            ->where('system_transaction_id', $this->request->transId)
            ->first();
    }

    private function accountData($model)
    {
        return [
            'account' => ['value' => (string) PaymentService::convertModelToKey($model)],
        ];
    }

    private function mapStatus($state)
    {
        switch ($state) {
            case Transaction::STATE_CREATED:
                return Response::STATUS_CREATED;
            case Transaction::STATE_COMPLETED:
                return Response::STATUS_CONFIRMED;
            case Transaction::STATE_CANCELLED:
            case Transaction::STATE_CANCELLED_AFTER_COMPLETE:
                return Response::STATUS_REVERSED;
            default:
                return Response::STATUS_FAILED;
        }
    }
}
