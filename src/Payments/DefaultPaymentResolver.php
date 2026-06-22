<?php

namespace Goodoneuz\PayUz\Payments;

use Goodoneuz\PayUz\Payments\Contracts\PaymentResolver;

/**
 * The fallback resolver used when the host application has not configured its
 * own under `payuz.payments.resolver`.
 *
 * It reproduces the behaviour of the old published hook files so existing
 * installs keep working: a model's key is its `id`, the key resolves back to an
 * `App\Models\User`, every amount is accepted, and the response is passed
 * through untouched. Real applications should ship their own resolver — see the
 * README "Payment hooks" section.
 */
class DefaultPaymentResolver implements PaymentResolver
{
    /**
     * {@inheritdoc} — old `model_key.php` default: `return $model->id;`
     */
    public function convertModelToKey($model)
    {
        return is_object($model) && isset($model->id) ? $model->id : null;
    }

    /**
     * {@inheritdoc} — old `key_model.php` default: `return \App\Models\User::find($key);`
     *
     * Guarded with class_exists so the package does not hard-depend on the host
     * application's User model; returns null when it is absent.
     */
    public function convertKeyToModel($key)
    {
        if (class_exists(\App\Models\User::class)) {
            return \App\Models\User::find($key);
        }

        return null;
    }

    /**
     * {@inheritdoc} — old `is_proper.php` default: `return true;`
     */
    public function isProperModelAndAmount($model, $amount)
    {
        return true;
    }

    /**
     * {@inheritdoc} — old `before_response.php` default: `return $response;`
     */
    public function beforeResponse($context, $request, array $response)
    {
        return $response;
    }
}
