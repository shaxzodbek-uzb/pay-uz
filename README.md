
# Для национальных платежных систем в Узбекистане
[Видео документация <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/0/09/YouTube_full-color_icon_%282017%29.svg/1200px-YouTube_full-color_icon_%282017%29.svg.png" width="26">](https://www.youtube.com/playlist?list=PLIU-yN_rFScVbbglNYmucY3TKzrxypEaP)

<a href="https://tirikchilik.uz/shaxzodbek-uzb" target="_blank"><img src="https://www.buymeacoffee.com/assets/img/custom_images/orange_img.png" alt="Buy Me A Coffee" style="height: 41px !important;width: 174px !important;box-shadow: 0px 3px 2px 0px rgba(190, 190, 190, 0.5) !important;-webkit-box-shadow: 0px 3px 2px 0px rgba(190, 190, 190, 0.5) !important;" ></a>

[![Latest Version on Packagist](https://img.shields.io/packagist/dt/goodoneuz/pay-uz.svg?style=flat)](https://packagist.org/packages/goodoneuz/pay-uz)
[![Build Status](https://img.shields.io/travis/shaxzodbek-uzb/pay-uz/master.svg?style=flat-square)](https://travis-ci.org/shaxzodbek-uzb/pay-uz)
[![Quality Score](https://img.shields.io/scrutinizer/g/shaxzodbek-uzb/pay-uz.svg?style=flat-square)](https://scrutinizer-ci.com/g/shaxzodbek-uzb/pay-uz)

**Featured**
------
- [Payme](http://payme.uz) - Merchant
- [Click](http://click.uz) - Merchant
- [Paynet](http://paynet.uz) - Merchant
- [Uzum Bank](https://uzumbank.uz) - Merchant
- [Stripe](https://stripe.com/) - Merchant(Subscribe)
- OFD fiscalization (онлайн-ККМ / virtual kassa) - pluggable drivers (IKPU/MXIK, VAT, fiscal sign/QR)
- Recurring charges / card tokenization - [Payme Subscribe](https://developer.help.paycom.uz) & [ATMOS](https://atmos.uz) (save card, OTP, charge)
- Card-acquiring aggregators - [Octo](https://octo.uz) & [Multicard](https://multicard.uz) / [Rahmat Pay](https://rhmt.uz) (Uzcard+Humo+Visa+MC, hosted checkout, saved-card, capture/refund/webhook)
- BNPL / installments - [Uzum Nasiya](https://uzum.uz/nasiya) (eligibility, tariffs, contract create/confirm/cancel/status)
- E-invoicing / ЭСФ - [Didox](https://didox.uz) (e-documents, E-IMZO signer seam, create/sign/accept/reject/cancel/status)

*По умолчанию для оплаты установлен "накопительный режим". Чтобы производить оплату в "Одноразовом режиме", вам необходимо изменить параметр в config/payuz.php ``'multi_transaction' => false``*

**Planned**
------
- Upay
- Visa
- Oson, Uzcard — `PaymentSystem::OSON` / `::UZCARD` exist as transaction labels, but
  there is no gateway behind them. Draft classes under `src/Http/Classes/{Oson,Uzcard}`
  are **not** wired into `PayUz::driver()` and are not autoloadable either (they declare
  `App\…` namespaces, which PSR-4 maps to the host application rather than this package).
  `driver('oson')` fails immediately and says so.


## Installation

You can install the package via composer:

```bash
composer require goodoneuz/pay-uz
```
Publishing required files of package:

```bash
php artisan vendor:publish --provider="Goodoneuz\PayUz\PayUzServiceProvider"
```
Migrate tables:

```bash
php artisan migrate
```

Seed settings:

```bash
php artisan db:seed --class="Goodoneuz\PayUz\database\seeds\PayUzSeeder"
```

## Usage
------
Placing routes for service in web.php

```php

//handle requests from payment system
Route::any('/handle/{paysys}',function($paysys){
    (new Goodoneuz\PayUz\PayUz)->driver($paysys)->handle();
});

//redirect to payment system or payment form
Route::any('/pay/{paysys}/{key}/{amount}',function($paysys, $key, $amount){
	$model = Goodoneuz\PayUz\Services\PaymentService::convertKeyToModel($key);
    $url = request('redirect_url','/'); // redirect url after payment completed
    $pay_uz = new Goodoneuz\PayUz\PayUz;
    $pay_uz
    	->driver($paysys)
    	->redirect($model, $amount, 860, $url);
});
```

### Payment hooks (resolver & events)

The gateways bridge to your application in two ways. **Neither writes or executes
runtime PHP files** — the old editable `app/Http/Controllers/Payments/*.php` hooks
(and the in-dashboard code "editor") have been removed.

**1. A resolver** for the operations that must return a value — mapping your
model to/from the payment key, validating callback amounts, and optionally
post-processing a gateway's response. Implement
`Goodoneuz\PayUz\Payments\Contracts\PaymentResolver` and point
`config/payuz.php` at it:

```php
// config/payuz.php
'payments' => [
    'resolver' => \App\Payments\AppPaymentResolver::class,
],
```

```php
namespace App\Payments;

use App\Models\Order;
use Goodoneuz\PayUz\Payments\Contracts\PaymentResolver;

class AppPaymentResolver implements PaymentResolver
{
    public function convertModelToKey($model)        { return $model->id; }
    public function convertKeyToModel($key)          { return Order::find($key); }
    public function isProperModelAndAmount($m, $amt) { return $m && (int) $m->amount === (int) $amt; }
    public function beforeResponse($context, $request, array $response) { return $response; }
}
```

The shipped `DefaultPaymentResolver` reproduces the old default hooks
(`$model->id`, `App\Models\User::find($key)`, accept-all, pass-through), so an
install that never customised them keeps working.

**2. Lifecycle events** for fire-and-forget side effects. Subscribe to them from
your `EventServiceProvider`:

| event (`Goodoneuz\PayUz\Payments\Events\…`) | old hook         | when                                   |
|---------------------------------------------|------------------|----------------------------------------|
| `PaymentBeforePay`                          | `before_pay.php` | model resolved, before a tx is created |
| `PaymentProcessing`                         | `paying.php`     | transaction created, payment underway  |
| `PaymentPaid`                               | `after_pay.php`  | payment completed                      |
| `PaymentCancelled`                          | `cancel_pay.php` | payment cancelled / reversed           |

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    \Goodoneuz\PayUz\Payments\Events\PaymentPaid::class => [
        \App\Listeners\FulfilOrder::class,
    ],
];
```

Each event exposes `$event->model` and `$event->transaction` (for
`PaymentBeforePay`, `$event->transaction` is the requested amount).

### Uzum Bank (Merchant API)

Uzum Bank uses the server-to-server "Merchant API" model: Uzum's processing
centre calls **your** endpoints for five operations — `check`, `create`,
`confirm`, `reverse`, `status`. Expose them on a single `{operation}` route:

```php
Route::post('/handle/uzum/{operation}', function () {
    return (new Goodoneuz\PayUz\PayUz)->driver('uzum')->handle();
})->where('operation', 'check|create|confirm|reverse|status');
```

Configure the credentials Uzum issues for your terminal in the control panel
(payment system `uzum`):

| param        | meaning                                                        |
|--------------|----------------------------------------------------------------|
| `login`      | HTTP Basic auth login                                          |
| `password`   | HTTP Basic auth password                                       |
| `service_id` | your Uzum `serviceId` (also validated against the request body) |
| `key`        | which `params` field identifies the order/model (default `id`) |

Authentication is HTTP Basic (`login:password`) plus a `serviceId` match. Amounts
on the wire are in **tiyin** (1 som = 100 tiyin); transactions are stored in som,
like the Payme driver.

### OFD fiscalization (онлайн-ККМ)

Under PKM No. 943 (23.11.2019) every settlement with the public must be
registered with a Fiscal Data Operator (OFD). The fiscalization layer turns an
order into a fiscal receipt — with IKPU/MXIK product codes, VAT and a fiscal
sign/QR — independently of which payment gateway took the money.

It is intentionally decoupled from the payment webhooks: you fiscalize after a
payment completes (e.g. in your own "transaction completed" handler) and attach
the result to the transaction. Nothing is written to executable hook files.

**Configure** the driver in `config/payuz.php` (`fiscalization` block). The
shipped default is `null` — a no-op that validates the receipt and returns a
synthetic sign — so a fresh install never blocks on credentials it lacks. Switch
to the `ofd` driver and point it at your OFD/virtual-kassa gateway in production:

```php
'fiscalization' => [
    'default'     => env('PAYUZ_FISCAL_DRIVER', 'null'), // 'null' | 'ofd'
    'vat_percent' => env('PAYUZ_FISCAL_VAT', 12),
    'drivers' => [
        'ofd' => [
            'endpoint'    => env('OFD_ENDPOINT'),    // your register-receipt URL
            'token'       => env('OFD_TOKEN'),       // bearer token
            'terminal_id' => env('OFD_TERMINAL_ID'),
        ],
        'null' => ['log' => env('PAYUZ_FISCAL_LOG', false)],
    ],
],
```

The `ofd` driver builds the standard **soliq fiscal-receipt** body (`Name`,
`SPIC` = MXIK, `PackageCode`, `GoodPrice`, `Price`, `Amount`, `VAT`,
`VATPercent`, `IsRefund`, `ReceivedCash`/`ReceivedCard`) and parses the
`FiscalSign` / `QRCodeURL` / `TerminalId` out of the response.

**Build a receipt and fiscalize it.** Amounts are in **tiyin** (1 som = 100
tiyin) and prices are VAT-inclusive; the VAT amount is extracted for you.

```php
use Goodoneuz\PayUz\Fiscalization\Receipt;
use Goodoneuz\PayUz\Fiscalization\ReceiptItem;
use Fiscalizer; // facade alias

$receipt = Receipt::sale($transaction->id, [
    new ReceiptItem(
        'Pro plan — 1 month',  // title (printed on the receipt)
        '10305001001000000',   // MXIK / IKPU (17 digits, from tasnif.soliq.uz)
        12_000_000,            // unit price in tiyin (120 000 so'm, VAT incl.)
        1,                     // quantity
        12,                    // VAT percent (12 standard, 0 exempt)
        '1495762'              // package code (optional)
    ),
])->payByCard();

$result = Fiscalizer::fiscalize($receipt); // uses the default driver

if ($result->isSuccessful()) {
    $transaction->attachFiscalReceipt($result); // stores sign/QR in detail['fiscal']
    // $result->fiscalSign(), $result->qr(), $result->receiptUrl()
}
```

Items can also be built from arrays (snake_case or common aliases):

```php
$receipt = Receipt::sale($order->id, [
    ['title' => 'Coffee', 'mxik' => '...', 'price' => 2_500_000, 'count' => 2, 'vat_percent' => 12],
]);
```

Use `Receipt::refund(...)` for returns and `withPayment($cashTiyin, $cardTiyin)`
for a mixed cash/card split (it must balance the total).

**Events.** `Fiscalizer::fiscalize()` emits
`Goodoneuz\PayUz\Fiscalization\Events\ReceiptFiscalized` on success and
`...\Events\FiscalizationFailed` otherwise — listen for these to persist, notify
or queue a retry.

**Custom OFD provider.** Implement `Fiscalization\Contracts\FiscalDriver` and
register it without touching the package:

```php
Fiscalizer::extend('my-ofd', function (array $config, $http) {
    return new \App\Fiscal\MyOfdDriver($config, $http);
});
```

> **Notes.** Direct submission to `ofd.uz` additionally requires PKCS#7 signing
> of the receipt with the taxpayer certificate — use a gateway that signs on its
> side, or add a signing driver via `extend()`. **Multikassa (multibank)** is a
> *local* cashier agent (`http://localhost:8080`), not a server-side API, so it
> is intentionally not shipped as a driver; add it via `extend()` if you run it
> on the same host. Confirm exact endpoints/fields against your provider's docs.

### Recurring charges / card tokenization (Payme Subscribe)

Save a customer's card once, then charge it again and again — for subscriptions
and one-click payments. The `Subscribe` facade is gateway-agnostic (Payme
Subscribe is the first driver). Amounts are in **tiyin**; the shipped default is
the `null` driver (simulates the happy path) so a fresh install works offline.

**Configure** `subscribe` in `config/payuz.php` and switch to `payme` in
production:

```php
'subscribe' => [
    'default' => env('PAYUZ_SUBSCRIBE_DRIVER', 'null'), // 'null' | 'payme'
    'drivers' => [
        'payme' => [
            'merchant_id' => env('PAYME_SUBSCRIBE_MERCHANT_ID'),
            'key'         => env('PAYME_SUBSCRIBE_KEY'), // secret X-Auth key — server only!
            'test'        => env('PAYME_SUBSCRIBE_TEST', false),
        ],
    ],
],
```

**Tokenize a card (with OTP), then charge it:**

```php
use Subscribe; // facade alias

// 1. Mint a token from the card (this call is browser-safe — id-only auth)
$card = Subscribe::driver('payme')->createCard($pan, $expire /* "MMYY" */, true);

// 2. Send + confirm the SMS code
Subscribe::driver('payme')->sendVerifyCode($card->token());
$card = Subscribe::verify($card->token(), $smsCode);   // fires CardVerified

// 3. Persist ONLY $card->token() (never the PAN), then charge any time:
$charge = Subscribe::charge($card->token(), 1_200_000, ['order_id' => $order->id]);
if ($charge->isPaid()) {                                // fires ChargePaid
    // $charge->id(), $charge->state(), $charge->cardNumber() (masked)
}
```

**Two-stage (hold / capture):**

```php
$held = Subscribe::authorize($card->token(), 2_500_000, ['order_id' => $order->id]); // state 5
Subscribe::capture($held->id());   // captures (state 4)  — fires HoldConfirmed
// or Subscribe::release($held->id());  // voids           — fires ChargeCancelled
```

**Security & rules enforced by the driver:** the `X-Auth` header is the cashbox
id **alone** for the browser-safe token-minting calls and `id:key` (secret key)
for every server-side call (`cards.check`/`remove`, all `receipts.*`); only the
**token** is ever persistable — never the PAN; all amounts are tiyin integers.
Gateway errors raise typed exceptions (`AuthorizationException`,
`InvalidAmountException`, `ReceiptNotFoundException`, `AccountException`, …);
unmapped/decline codes surface as `SubscribeException` carrying the raw code.

**ATMOS** is a second `Subscribe` driver (`subscribe.default = 'atmos'`). It speaks
the same contract — `createCard` → `verifyCard` → `createReceipt`/`payReceipt` —
over ATMOS's OAuth2 card-vault API. Notable differences the driver handles for you:
amounts are already **tiyin** (no conversion); auth is an OAuth `client_credentials`
token (cached, auto-refreshed); card binding returns a `binding_id` that
`verifyCard` swaps for the real `card_token`; and the OTP for saved-token charges
is fixed. Configure `consumer_key` / `consumer_secret` / `store_id` / `api_key`
under `subscribe.drivers.atmos`. Two ATMOS limits are explicit: `confirmHold()`
throws (no verified hold endpoint) and refunds are whole-transaction
(`cancelReceipt`). The merchant-cabinet callback is verified with the
ATMOS-specific `Subscribe::driver('atmos')->verifyCallback($payload)` /
`parseCallback($payload)` (the callback `amount` is unauthenticated — reconcile via
`getReceipt()` before granting value).

### Card-acquiring aggregator (Octo)

One REST integration for Uzcard + Humo + Visa + Mastercard + international cards,
with hosted checkout, saved-card charges, two-stage capture, refunds and webhooks.
The `Checkout` facade is gateway-agnostic (Octo is the first driver; ATMOS /
Multicard follow). **Amounts are tiyin** in your code — the driver converts to
Octo's som. Default driver is `null` (simulator).

**Credentials live in the control panel**, the same as Payme and Click: run the
seeder, then open `/payment/payment_systems`, edit **Octo** and fill in the fields.
They are stored in `payment_system_params` and read on every call, so a key change
needs no deploy.

```bash
php artisan db:seed --class="Goodoneuz\PayUz\Database\Seeds\PayUzSeeder"
```

| Param | Meaning |
|---|---|
| `shop_id`, `secret` | issued by Octo; sent in the body of every request |
| `unique_key` | webhook signing key — notifications are rejected without it |
| `test` | `1` for the sandbox, `0` for live (a real flag, not a separate host) |
| `notify_url` | payment webhook |
| `bind_notify_url` | where Octo delivers a card token after binding |
| `return_url`, `language`, `receipt_email`, `bindable_methods` | optional |

`config/payuz.php` keeps the same keys as a fallback, so an app that prefers env
vars can set them there instead — the panel simply wins where both are present, and
a blank panel field never overrides a configured value:

```php
'checkout' => [
    'default' => env('PAYUZ_CHECKOUT_DRIVER', 'null'), // 'null' | 'octo'
    'drivers' => [
        'octo' => [
            'shop_id'    => env('OCTO_SHOP_ID'),
            'secret'     => env('OCTO_SECRET'),
            'unique_key' => env('OCTO_UNIQUE_KEY'),
            'test'       => env('OCTO_TEST', true), // false to go live
            'notify_url' => env('OCTO_NOTIFY_URL'),
        ],
    ],
],
```

**Hosted checkout (redirect):**

```php
use Checkout;
use Goodoneuz\PayUz\Checkout\Payment;

$result = Checkout::pay(
    Payment::make(1_200_000, $order->id)        // tiyin
        ->describedAs('Order #'.$order->id)
        ->returnTo(route('checkout.return'))
        ->notifyAt(route('checkout.webhook'))    // Octo will POST the outcome here
);

return redirect($result->payUrl());              // send the customer to Octo
```

**Webhook route** — verifies the signature, normalizes the outcome and emits the
event (`PaymentSucceeded` / `PaymentFailed` / `PaymentRefunded`):

```php
Route::post('/checkout/webhook', function () {
    $result = Checkout::webhook(request()->all(), request()->headers->all());
    // act on $result, or (recommended) re-confirm via Checkout::status($order->id)
    return response('', 200);
});
```

Octo signs notifications as `sha1(unique_key + octo_payment_UUID + status)`, and
that signature covers **only the payment id and status — never the amount**. Treat
the webhook as a trigger and reconcile with `Checkout::status($order->id)` before
granting value.

**Saving a card (tokenization).** `chargeToken()` spends a token; `CardBinder`
mints one. Binding is three steps because that is what Octo does: it debits a
small amount, has the issuer send an SMS code, reverses the debit, and only then
delivers the token — on a **separate callback**, not in the confirmation response.
So a card is confirmed before it is chargeable.

```php
use Goodoneuz\PayUz\Checkout\CardBinding;

// 1. send the card; Octo texts the customer a code
$session = Checkout::bindCard(
    CardBinding::make($pan, '1229', (string) Str::uuid())   // expiry is "MMYY"
        ->heldBy('ALISHER KARIMOV')
        ->withPhone($user->phone)
        ->withCvc($cvc)
        ->notifyAt(route('cards.octo.callback'))
);
// $session->bindingId(), ->verifyId(), ->phone(), ->secondsLeft()

// 2. the customer enters the code
$session = Checkout::confirmBinding($session->bindingId(), $session->verifyId(), $code);

// 3. Octo calls back with the token
Route::post('/cards/octo/callback', function () {
    $card = Checkout::bindCallback(request()->all());

    if ($card->isBound()) {
        // persist $card->token() only — never a PAN
        PaymentCard::where('binding_reference', $card->reference())
            ->update(['token' => $card->token(), 'last_four' => $card->lastFour()]);
    }

    // ALWAYS 200 + this body: Octo retries three times and then CANCELS the token.
    return response()->json(\Goodoneuz\PayUz\Checkout\BoundCard::acknowledgement());
})->name('cards.octo.callback');
```

Then charge it with no redirect, and revoke it when the customer deletes the card:

```php
$result = Checkout::charge($token, Payment::make(1_200_000, $order->id));

if (!$result->isSuccessful()) {
    // Octo asked for an SMS code (status wait_user_action)
    $info = Checkout::driver('octo')->verificationInfo($result->paymentId());
    // … collect the code, then:
    $result = Checkout::driver('octo')->confirmPayment($paymentId, $info->verifyId(), $code);
}

Checkout::revokeToken($token);
```

Octo documents binding for **Uzcard and Humo only** — `bindableMethods()` refuses
anything else before the card is charged for the verification. Taking a PAN on your
own page also requires PCI DSS certification on your Octo contract; without it, use
the hosted checkout above instead.

**Saved-card charge, two-stage and refunds:**

```php
$result = Checkout::charge($cardToken, Payment::make(1_200_000, $order->id)); // no redirect
$hold   = Checkout::pay(Payment::make(1_200_000, $order->id)->authorizeOnly());
Checkout::capture($uuid, 1_200_000);  // capture a held payment (Octo needs the amount)
Checkout::refund($uuid, 600_000);     // partial refund
```

> **Octo caveats (documented in the driver):** Octo bills in **som**, so the
> driver divides your tiyin by 100. HTTP is always 200 — failure is the response
> `error` field, surfaced as `CheckoutException`. `capture()`/`refund()` take the
> `octo_payment_UUID`, but `status()` takes your **`shop_transaction_id`** (order
> id). The **webhook signature recipe is not byte-precisely documented**: the
> driver checks `sha1(unique_key . uuid . status)` with `hash_equals`, but you
> should confirm it against a live callback and re-verify via `status()` before
> mutating an order.

**Multicard** is a second `Checkout` driver (`checkout.default = 'multicard'`) —
same `Checkout` API (`pay` / `charge` / `capture` / `refund` / `status` /
`webhook`). It speaks Multicard's REST API: a cached bearer token (`POST /auth`),
real HTTP verbs (invoice = `POST`, capture = `PUT`, refund = `DELETE`), and the
`{success}` envelope. Unlike Octo, **amounts are already tiyin** (no conversion).
Configure `application_id` / `secret` / `store_id` / `callback_url` under
`checkout.drivers.multicard`; `base_url` selects prod vs sandbox. Two things to
confirm with Multicard: which **`callback_scheme`** your store uses (`webhooks` →
`sha1(uuid·invoice_id·amount·secret)`, or `success` → `md5(store_id·invoice_id·amount·secret)`),
and that `capture`/`refund`/`status` take the payment **uuid**. OFD line items and
`split` are passed through via `Payment::with([...])`.

> **Rahmat Pay** is not a separate gateway — it is the Multicard acquiring rail
> (its hosted checkout renders on `app.rhmt.uz`). Use the `multicard` driver; the
> `rahmat` alias (`Checkout::driver('rahmat')`) resolves to it with the same
> `multicard` config for discoverability.

### BNPL / installments (Uzum Nasiya)

Buy-now-pay-later is a **credit-contract lifecycle**, not card acquiring, so it
has its own `Bnpl` facade (Uzum Nasiya is the first driver). The flow: check the
buyer's eligibility, calculate installment tariffs, create a contract, let the
buyer sign it in Uzum's WebView, then confirm. Amounts are **tiyin** at the
facade; the driver converts. Default driver is `null` (simulator).

```php
use Bnpl;

$elig = Bnpl::checkEligibility($phone);          // e.g. "998901234567"
if ($elig->isBlocked())   { /* stop */ }
if ($elig->mustRegister()) { return redirect($elig->webviewUrl()); }

$plans = Bnpl::calculate($elig->buyerId(), [
    ['product_id' => $p->id, 'price' => 1_200_000, 'amount' => 1], // price in tiyin
]);

$contract = Bnpl::createContract($elig->buyerId(), $plans[0]->tariffId(), [
    ['name' => $p->name, 'price' => 1_200_000, 'amount' => 1, 'category' => 1, 'unit_id' => 1],
], $order->id);                                   // fires ContractCreated

return redirect($contract->webviewPath());        // buyer signs in Uzum's WebView

// after the buyer returns / on a status poll:
Bnpl::confirm($contract->contractId());           // fires ContractConfirmed
// Bnpl::cancel($contract->orderId());             // note: cancel uses orderId(), not contractId()
$status = Bnpl::status($contract->contractId());  // poll-only — Nasiya has no signed webhook
```

Configure `bnpl.drivers.uzum_nasiya` with the partner **Bearer JWT** (issued at
onboarding — no self-serve key, no published sandbox). Two integration-time items
the driver flags inline: **amounts are decimal som on the wire** ([INFERRED] — the
driver converts tiyin↔som; confirm against a live payload), and whether activation
is **WebView vs partner-driven OTP** (`otp_mode`, default `webview`) is uncertain.
Refunds are **full-only** (`cancel`); there is no partial refund.

> **Alif Nasiya** is a planned second BNPL driver — the `Bnpl` layer is ready for
> it — but Alif's Nasiya API is partner-gated with no public spec, so it is
> deferred until onboarding rather than guessed.

### E-invoicing / ЭСФ (Didox)

Issue electronic invoices (ЭСФ) and other e-documents through an operator. This is
not a payment flow, so it has its own `Einvoice` facade (Didox is the first
driver). The package performs **no cryptography**: Uzbek e-documents are signed
with E-IMZO (PKCS#7), which the host app supplies through a `Signer` (or a
pre-signed blob). Amounts are **tiyin** in your code; the driver emits decimal-som
on the wire. Default driver is `null`.

```php
use Einvoice;
use Goodoneuz\PayUz\Einvoice\{Document, InvoiceItem, Counterparty};
use Goodoneuz\PayUz\Einvoice\Signers\CallableSigner;

Einvoice::driver('didox')->login(new Counterparty($myTin), $password); // or set user_key

$doc = Document::invoice(
    new Counterparty($myTin, 'My Company'),
    new Counterparty($buyerTin, 'Buyer LLC'),
    [new InvoiceItem('00702001001000001', 'Pro plan', 1_200_000, 1, 12)] // price tiyin, net
)->factura('A-1', '2026-06-20');

$res = Einvoice::createDocument($doc);            // fires DocumentCreated

// Sign with E-IMZO (in your app) and submit. Two ways:
Einvoice::useSigner(new CallableSigner(fn ($b64) => $yourEimzo->signPkcs7($b64)));
Einvoice::signAndSubmit($res->documentId());      // toSign → sign → submit → DocumentSigned
// — or do it yourself: $blob = $eimzo->sign(Einvoice::toSign($id)); Einvoice::submit($id, $blob);
```

Configure `einvoice.drivers.didox` with the partner token (`Partner-Authorization`
header — switchable via `partner_header`). State is **poll-only** (`status`/`list`;
no webhook). Several wire details (response keys, the exact sign field, the
to-sign source path, the `doc_status` map) are best-effort and flagged
**UNCERTAIN** in the driver — confirm against the Didox sandbox.

**Exception:**
------

PaymentException 

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email shaxzodbek.qambaraliyev@gmail.com instead of using the issue tracker.

> ⚠️ **Upgrade note (code "editor" removed; hooks are now code).**
> The runtime code "editor" — and the `app/Http/Controllers/Payments/*.php` files
> it wrote and `PaymentService` `require`d — have been removed. They were a
> write-PHP-then-execute primitive (the unauthenticated form was CVE-2026-31843; an
> authenticated-CSRF variant remained afterwards). Move your hooks into versioned
> code: implement a [`PaymentResolver`](#payment-hooks-resolver--events) and
> subscribe to the `Payments\Events\*` lifecycle events. The control-panel routes
> remain behind the `auth` middleware by default (`control_panel.middleware`); the
> publish tag `pay-uz-editable` is now `pay-uz-config`. See
> [CHANGELOG](CHANGELOG.md) for details.

## Credits

- [Shaxzodbek](https://github.com/shaxzodbek-uzb)
- [Azizbek](https://github.com/azizbekeshonaliyev)
- [Rustam Mamadaminov](https://github.com/rustamwin)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
