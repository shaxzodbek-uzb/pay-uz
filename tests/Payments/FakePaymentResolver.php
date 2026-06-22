<?php

namespace Goodoneuz\PayUz\Tests\Payments;

use Goodoneuz\PayUz\Payments\Contracts\PaymentResolver;

/**
 * Records the arguments PaymentService forwards and returns canned values, so the
 * delegation can be asserted without a database or container.
 *
 * Not a *Test.php file, so PHPUnit does not collect it as a test case.
 */
class FakePaymentResolver implements PaymentResolver
{
    /** @var array<int, array> every call as [method, ...args] */
    public $calls = [];

    public $modelKey   = 'the-key';
    public $model      = 'the-model';
    public $proper     = true;
    public $response   = ['resolver' => 'ran'];

    public function convertModelToKey($model)
    {
        $this->calls[] = ['convertModelToKey', $model];

        return $this->modelKey;
    }

    public function convertKeyToModel($key)
    {
        $this->calls[] = ['convertKeyToModel', $key];

        return $this->model;
    }

    public function isProperModelAndAmount($model, $amount)
    {
        $this->calls[] = ['isProperModelAndAmount', $model, $amount];

        return $this->proper;
    }

    public function beforeResponse($context, $request, array $response)
    {
        $this->calls[] = ['beforeResponse', $context, $request, $response];

        return $this->response;
    }
}
