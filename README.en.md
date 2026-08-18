# Uzbekistan payment systems for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/goodoneuz/pay-uz.svg?style=flat-square)](https://packagist.org/packages/goodoneuz/pay-uz)
[![Total Downloads](https://img.shields.io/packagist/dt/goodoneuz/pay-uz.svg?style=flat-square)](https://packagist.org/packages/goodoneuz/pay-uz)
[![License](https://img.shields.io/packagist/l/goodoneuz/pay-uz.svg?style=flat-square)](LICENSE.md)

Merchant integrations, card acquiring, recurring charges, fiscalization, BNPL and
e-invoicing for Uzbekistan, behind one package.

> **The full documentation is [README.md](README.md).** This page is the short
> version; each layer below links to its own section there.
> Shuningdek [o'zbekcha](README.uz.md).

## What is in it

Each layer is a facade over pluggable drivers, and every one ships a `null` driver
so a fresh install runs offline.

| Layer | Facade | Providers |
|---|---|---|
| Merchant callbacks (redirect + webhook) | `PayUz` | [Payme](https://payme.uz), [Click](https://click.uz), [Paynet](https://paynet.uz), [Uzum Bank](https://uzumbank.uz) |
| Card acquiring (hosted checkout, saved card, capture/refund) | `Checkout` | [Octo](https://octo.uz), [Multicard / Rahmat Pay](https://multicard.uz) |
| Recurring charges / card tokenization | `Subscribe` | [Payme Subscribe](https://developer.help.paycom.uz), [ATMOS](https://atmos.uz), [Stripe](https://stripe.com) |
| OFD fiscalization (virtual cash register) | `Fiscalizer` | pluggable — IKPU/MXIK, VAT, fiscal sign/QR |
| BNPL / installments | `Bnpl` | [Uzum Nasiya](https://uzum.uz/nasiya) |
| E-invoicing / ESF | `Einvoice` | [Didox](https://didox.uz) |

**Amounts are tiyin everywhere in your code.** Each driver converts to whatever its
gateway wants at its own boundary — Octo bills in decimal som, for instance — so you
never have to do it at a call site.

Planned: Upay, Visa. `PaymentSystem::OSON` and `::UZCARD` exist as transaction
labels only; there is no gateway behind them.

## Installation

```bash
composer require goodoneuz/pay-uz
```

Publish the package files:

```bash
php artisan vendor:publish --provider="Goodoneuz\PayUz\PayUzServiceProvider"
```

Migrate, then seed:

```bash
php artisan migrate
```

```bash
php artisan db:seed --class="Goodoneuz\PayUz\Database\Seeds\PayUzSeeder"
```

Credentials for Payme, Click, Uzum and Octo are then entered in the control panel at
`/payment/payment_systems`. The published `config/payuz.php` keeps the same keys as
an env-driven fallback, and a blank panel field never overrides a configured value.

## Usage

Merchant flow — one route to receive the payment system's callbacks, one to send the
customer off to pay:

```php
// handle requests from a payment system
Route::any('/handle/{paysys}', function ($paysys) {
    (new Goodoneuz\PayUz\PayUz)->driver($paysys)->handle();
});

// redirect to the payment system or its payment form
Route::any('/pay/{paysys}/{key}/{amount}', function ($paysys, $key, $amount) {
    $model = Goodoneuz\PayUz\Services\PaymentService::convertKeyToModel($key);
    $url   = request('redirect_url', '/'); // where to land after payment

    (new Goodoneuz\PayUz\PayUz)
        ->driver($paysys)
        ->redirect($model, $amount, 860, $url);
});
```

Card acquiring — a hosted checkout, with the amount in tiyin:

```php
use Checkout;
use Goodoneuz\PayUz\Checkout\Payment;

$result = Checkout::pay(
    Payment::make(1_200_000, $order->id)        // tiyin
        ->describedAs('Order #'.$order->id)
        ->returnTo(route('checkout.return'))
        ->notifyAt(route('checkout.webhook'))
);

return redirect($result->payUrl());             // send the customer to the gateway
```

Saved cards, two-stage capture, refunds, webhooks and Octo card binding are covered
in the [Checkout section of README.md](README.md#card-acquiring-aggregator-octo).

Reacting to a payment is done with a `PaymentResolver` plus the `Payments\Events\*`
lifecycle events — see [Payment hooks](README.md#payment-hooks-resolver--events).
If you are upgrading from before 3.0.0, read the upgrade note at the end of
[README.md](README.md): the runtime code "editor" has been removed.

Failures raise `PaymentException`; the Checkout, Subscribe, Bnpl and Einvoice layers
raise their own exception types.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover a security issue, please email shaxzodbek.qambaraliyev@gmail.com
instead of using the issue tracker.

## Credits

- [Shaxzodbek](https://github.com/shaxzodbek-uzb)
- [Azizbek](https://github.com/azizbekeshonaliyev)
- [Rustam Mamadaminov](https://github.com/rustamwin)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see the [License File](LICENSE.md) for more information.
