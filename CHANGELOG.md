# Changelog

All notable changes to `pay-uz` will be documented in this file

## Unreleased

### Added

- **Octo card binding (tokenization).** New `Checkout\Contracts\CardBinder`
  capability, implemented by the Octo driver, with `CardBinding`, `BindingSession`
  and `BoundCard` value objects and `Checkout::bindCard()` /
  `confirmBinding()` / `bindCallback()` / `revokeToken()` on the manager. Binding
  is modelled as three steps because Octo delivers the token on a separate
  callback rather than in the confirmation response, so a card is confirmed before
  it is chargeable. `BoundCard` derives the BIN and last four digits from the
  callback and drops the PAN it arrives with; `BoundCard::acknowledgement()` is the
  exact reply Octo requires — it retries three times and then cancels the token
  without it.

- `OctoDriver::confirmPayment()` for the SMS step Octo asks for on card and
  saved-token charges (`wait_user_action`), and `OctoDriver::cancel()` for
  releasing a hold via `set_accept`.

- Octo config keys `bind_notify_url`, `receipt_email` and `bindable_methods`.

- **Checkout credentials now come from the control panel.** `CheckoutManager` reads
  `payment_system_params` — the table Payme, Click and Uzum already use — and merges
  it over the config file, so Octo is set up at `/payment/payment_systems` like every
  other system instead of only through env. The lookup is lazy and fails soft (no
  table yet, no framework in unit tests), and blank panel fields never override a
  configured value. `PayUzSeeder` seeds the Octo system and its params;
  `PaymentSystem::OCTO` and `::MULTICARD` were added.

  Panel fields are text, so the driver now parses `test` properly — `(bool) "false"`
  is `true` in PHP, which would silently flip a live shop into test mode — and
  accepts `bindable_methods` as a comma-separated list.

### Fixed

- **Octo status reads hit a URL that does not exist.** `status()` posted to
  `/check_status`; Octo has no such endpoint (help.octo.uz/check-status.html) — the
  read goes through `/prepare_payment` with a minimal body, where omitting
  `total_sum` is what makes it a read rather than a second transaction.

- **Octo cancellations were reported as pending.** The status map knew only
  `cancelled`, while Octo documents `canceled`. A cancelled payment stayed
  indefinitely in "pending" for every caller.

- **Refunds were reported as complete the moment Octo accepted them.** `error: 0`
  only acknowledges the request; the refund itself reports
  `succeeded` / `pending` / `failed`, and only the first is a refund.

- **Saved-token charges omitted a required field.** Octo documents `email` as
  required on `/pay`; it is now sent, falling back to the new `receipt_email`
  config for customers who have no address of their own.

- **Card binding was impossible as shipped, in two ways — both found against the
  live API, both contradicting the published field table.** `/bind_card` requires
  **`notify_url` as well as `bind_notify_url`** (the docs mark both optional; the
  live API answers `Wrong params: [bindNotifyUrl, notifyUrl] must not be null or
  blank`, and validates this before it even looks up the shop), and it accepts
  **exactly one phone shape** — 12 digits, country code, no `+` and no separators —
  rejecting the `+998…` form most applications store with `Wrong phone format`.
  `CardBinding::withPhone()` now normalises, `notifyPaymentsAt()` supplies the
  second URL, and both URLs are required up front.

  Conversely `return_url`, which the docs mark **required**, is accepted as absent
  by the live API — it is now sent when present instead of being enforced. Same for
  `cardHolderName` and `cvc2`: omitted rather than padded with placeholder values.

- **Status reads survive Octo's rate limit.** Octo allows roughly one read per
  second per transaction and answers `Requests too often. Minimum delay is 1 second`
  beyond that — which a caller reads as "state unknown". A webhook, a return page
  and a reconciliation sweep collide on the same order routinely, so `status()` now
  pauses and retries once; other errors still propagate untouched.

- **A missing `notify_url` is now caught before the request, not by Octo.** It is
  mandatory on `/prepare_payment`; sending it as `null` produced an opaque gateway
  error. It now raises a `CheckoutException` naming the missing setting.

- Webhook verification now also accepts the signature in the `hash_key` field and
  compares case-insensitively — Octo documents neither the field choice nor the hex
  case, and merchants have been issued both.

- Unknown Octo statuses map to `pending` rather than being treated as terminal, so
  a state Octo adds later cannot cancel or settle an order.

- Dropped the unused `orchestra/testbench` dev dependency: nothing in the suite
  used it, and its pinned Laravel 5.7/8.x range blocked `composer install`.

- Declared `illuminate/database` and `illuminate/http`. The package imports from
  both across 22 files but listed only `illuminate/support`, so it relied on the
  host application happening to provide them. Constraints are `*`, matching the
  existing `illuminate/support` entry — this package deliberately floats them so
  it installs against whatever Laravel version the application already has.

- Click redirect: the legacy POST form to `https://my.click.uz/pay/` was shut
  down by CLICK. `Click::getRedirectParams()` now builds the CLICK-Button
  payment link (GET to `https://my.click.uz/services/pay`). Shop API
  callbacks are unchanged.

## 4.0.0 - 2026-06-21

Expands the package from "Payme + Click webhooks" into a multi-rail Uzbekistan
fintech toolkit: new **OFD fiscalization**, **card tokenization / recurring**
(Payme Subscribe, ATMOS), **card-acquiring aggregators** (Octo, Multicard/Rahmat),
**BNPL** (Uzum Nasiya), and **e-invoicing / ЭСФ** (Didox) layers, each behind its
own facade on a shared `Support\Http` transport, with `null`-driver defaults so a
fresh install works offline. **Major** version because the runtime code "editor"
and its `require`-based payment hooks are removed in favour of a resolver + events
(see Breaking).

### Added

- **E-invoicing layer (the `Einvoice` facade)** with **Didox** as the first driver
  — issue ЭСФ and other e-documents (create → sign → submit/accept/reject/cancel →
  status), its own layer (not a payment flow):
  - `EinvoiceDriver` contract resolved by `EinvoiceManager` behind the `Einvoice`
    facade (config under `payuz.einvoice`). Value objects `Document`, `InvoiceItem`,
    `Counterparty`, `DocumentStatus`, `EinvoiceResult`; a `Som` helper for the
    single tiyin↔decimal-som-string boundary; events `DocumentCreated` /
    `DocumentSigned` / `DocumentRejected` / `DocumentCancelled`; a `null` default.
  - **The package ships zero cryptography.** E-IMZO PKCS#7 signing is a `Signer`
    seam (`NullSigner` default throws; `CallableSigner` wraps a closure); the
    manager's `signAndSubmit()` wires toSign → sign → submit, or you pass a
    pre-signed blob to the driver directly.
  - `DidoxDriver`: `Partner-Authorization` + `user-key` auth, decimal-som amounts,
    poll-only status (no webhook). Wire details not byte-confirmed in public docs
    are flagged UNCERTAIN inline. (Live use needs a Didox partner token.)
- **Note:** Alif Nasiya was evaluated as a second BNPL driver but **deferred** — its
  Nasiya API is partner-gated with no public spec, so no driver was fabricated.

- **BNPL / installments layer (the `Bnpl` facade)** with **Uzum Nasiya** as the
  first driver — a credit-contract lifecycle (its own layer, not Checkout/Subscribe):
  - `BnplDriver` contract resolved by `BnplManager` behind the `Bnpl` facade
    (config under `payuz.bnpl`): `checkEligibility` → `calculate` →
    `createContract` → `confirm`/`cancel` → `status`, with `extend()` for new BNPL
    gateways. Value objects `Eligibility`, `InstallmentPlan`, `Contract`,
    `ContractStatus`, `ContractResult`.
  - `UzumNasiyaDriver` (coded against Uzum's official Nasiya Partner API OpenAPI):
    partner Bearer JWT auth, tiyin↔som conversion at the boundary, the two-id
    handling (confirm/status use `contract_id`, cancel uses the `order` id), and
    `response_code`→retryability for confirm/cancel. Two onboarding-time unknowns
    are flagged inline (som-vs-tiyin wire unit; WebView vs `/v3` OTP activation).
  - Events `ContractCreated`, `ContractConfirmed`, `ContractCancelled`, and a
    `null` driver default. (Live/E2E needs an Uzum partner token; the unit-tested
    driver ships now.)

- **Multicard driver for the `Checkout` layer.** A second card-acquiring
  aggregator (hosted checkout + saved-card charge + hold-capture + full/partial
  refund + webhook) over Multicard's REST API: a cached bearer token (`POST /auth`,
  refreshed + retried once on 401), the real HTTP verbs (invoice `POST`, capture
  `PUT`, refund `DELETE`, status `GET`), the `{success}` envelope mapped to typed
  exceptions, **tiyin pass-through** (no conversion), and both webhook signature
  schemes selected by `callback_scheme`.
- **`Support\Http\HttpClient::request($method, …)`** — the shared transport now
  supports arbitrary HTTP verbs (GET/PUT/DELETE), not just POST.
- **`rahmat` Checkout alias.** "Rahmat Pay" is not a separate processor — it is the
  Multicard acquiring rail (its checkout renders on app.rhmt.uz), so
  `Checkout::driver('rahmat')` resolves to the Multicard driver with the
  `multicard` config. No separate driver is shipped (none is warranted).

- **ATMOS driver for the `Subscribe` layer.** ATMOS's verified API is a server-side
  card-vault + OTP flow (OAuth2 → card bind/confirm → create/pre-apply/apply), which
  matches the `SubscribeDriver` contract — so ATMOS is a Subscribe driver, not a
  Checkout one. The driver handles the OAuth `client_credentials` token (cached +
  auto-refreshed), passes amounts through as **tiyin** (no conversion), maps the
  `result.code == 'OK'` envelope to typed exceptions, transposes the `MMYY`→`YYmm`
  expiry, and swaps the bind's `binding_id` for the confirmed `card_token`.
  `confirmHold()` and partial refunds are unsupported (no verified ATMOS endpoint)
  and throw / fall back to whole-transaction `cancelReceipt`. ATMOS-specific
  `verifyCallback()` / `parseCallback()` handle the merchant-cabinet webhook.
- **`Support\Http\HttpClient::postForm()`** — form-encoded POST on the shared
  transport (for OAuth `client_credentials` token requests and similar).

- **Card-acquiring aggregator layer (the `Checkout` facade)** with **Octo** as the
  first driver — one REST integration for Uzcard/Humo/Visa/Mastercard with hosted
  checkout, saved-card charges, two-stage capture, refunds and webhooks:
  - `CheckoutDriver` contract resolved by `CheckoutManager` behind the `Checkout`
    facade (config under `payuz.checkout`), with `extend()` and the shared
    `Support\Http` transport. `Payment` (request) + `PaymentResult` (normalized,
    vendor-agnostic status) value objects.
  - `OctoDriver`: converts the package's tiyin to Octo's som, branches on the
    `error` envelope (HTTP is always 200), maps Octo's status set to the
    normalized statuses, and verifies webhook signatures (`hash_equals`).
  - `webhook()` verifies the signature before emitting `PaymentSucceeded` /
    `PaymentFailed` / `PaymentRefunded`; an unverified payload raises
    `WebhookException` and is never acted on.

- **Card tokenization + recurring charges (the `Subscribe` facade).** A
  gateway-agnostic layer for save-card / one-click / subscription billing, with
  the **Payme Subscribe API** as the first driver:
  - `SubscribeDriver` contract resolved by `SubscribeManager` behind the
    `Subscribe` facade (config under `payuz.subscribe`), with `extend()` for new
    gateways and the shared `Support\Http` transport.
  - `Card`, `VerifyCode` and `Charge` value objects (tiyin amounts, receipt state
    codes); the lifecycle is createCard → sendVerifyCode → verify → charge, plus
    two-stage `authorize`/`capture`/`release` (holds).
  - `PaymeDriver` enforcing the Subscribe X-Auth rule (cashbox `id` alone for the
    browser-safe `cards.create`/`get_verify_code`/`verify`; `id:key` for
    `cards.check`/`remove` and all `receipts.*`), JSON-RPC error → typed
    exceptions, and a `null` driver default.
  - Events `CardVerified`, `ChargePaid`, `HoldConfirmed`, `ChargeCancelled`.
  - **Never persists the PAN — only the returned token.**
- **Shared HTTP layer `Support\Http`** (`HttpClient` seam + `CurlHttpClient` +
  `JsonRpcClient`, `TransportException`/`JsonRpcException`), now used by the
  fiscalization and subscribe layers (and future aggregator/e-invoice drivers).

- **OFD fiscalization layer (онлайн-ККМ).** A gateway-agnostic way to register
  fiscal receipts with a Fiscal Data Operator (PKM No. 943) and obtain the fiscal
  sign / QR:
  - Value objects `Fiscalization\Receipt` / `ReceiptItem` / `FiscalResult` —
    tiyin-integer amounts, VAT-inclusive prices, and `Receipt::sale()/refund()`
    factories with a cash/card payment split.
  - `Mxik` (17-digit IKPU/MXIK validation) and `Vat` (rate set `{0, 12}`,
    pure-integer round-half-up VAT extraction) helpers.
  - A `FiscalDriver` contract resolved by `FiscalizationManager` behind the new
    `Fiscalizer` facade, with `extend()` for custom OFD providers and an
    `HttpClient` transport seam (default cURL, no new dependencies).
  - Drivers: `null` (validates + returns a synthetic sign; the safe default) and
    `ofd` (generic soliq fiscal-receipt HTTP gateway with tolerant response
    parsing and QR-from-parts derivation).
  - `ReceiptFiscalized` / `FiscalizationFailed` events, and a
    `Transaction::attachFiscalReceipt()` helper that stores the result under the
    transaction's `detail` JSON. Configure under `payuz.fiscalization`.
- **Event/resolver-based payment hooks.** Application behaviour now lives in
  versioned code instead of runtime-writable PHP files:
  - A `Payments\Contracts\PaymentResolver` (default
    `Payments\DefaultPaymentResolver`, configured under `payuz.payments.resolver`)
    for the value-returning operations: `convertModelToKey`, `convertKeyToModel`,
    `isProperModelAndAmount`, `beforeResponse`.
  - Lifecycle events `Payments\Events\PaymentBeforePay`, `PaymentProcessing`,
    `PaymentPaid` and `PaymentCancelled` — subscribe from your `EventServiceProvider`
    instead of editing `paying.php` / `after_pay.php` / etc.

### Removed

- **The runtime code "editor" and its `require`-based hooks.** Deleted
  `ApiController::file_put`, the `POST /payment/api/editable/update` route, the
  `editors` page/controller action and `editors.blade.php`, and the published
  `app/Http/Controllers/Payments/*.php` hook files (no longer `require`d). The
  `PaymentService` methods that used to `require` those files now call the
  configured resolver / dispatch events (see Added).

### Security

- **Removed the residual authenticated-CSRF → RCE surface left after 3.0.0.**
  3.0.0 fixed the *unauthenticated* RCE (CVE-2026-31843) but the
  write-PHP-then-`require` architecture remained, gated only behind `auth`.
  Because the writer route used `Route::any` (so it accepted `GET`, which
  Laravel's CSRF middleware does not cover), a logged-in user could be lured into
  writing a hook file via a crafted link. Deleting the editor and the
  `require`-based hooks removes the write-and-execute primitive entirely.

### Breaking

- **Payment hooks moved from editable files to a resolver + events.**
  `PaymentService` no longer reads `app/Http/Controllers/Payments/*.php`. Migrate:
  - `model_key.php` / `key_model.php` / `is_proper.php` / `before_response.php` →
    implement `Payments\Contracts\PaymentResolver` and set
    `payuz.payments.resolver` to your class. The shipped `DefaultPaymentResolver`
    preserves the old defaults (`$model->id`, `App\Models\User::find($key)`,
    accept-all, pass-through).
  - `before_pay.php` / `paying.php` / `after_pay.php` / `cancel_pay.php` → listen
    for `PaymentBeforePay` / `PaymentProcessing` / `PaymentPaid` / `PaymentCancelled`.
  - The `php artisan vendor:publish` tag `pay-uz-editable` is renamed to
    `pay-uz-config` and no longer publishes hook files (only the config).

## 3.0.0 - 2026-06-20

Security release. Fixes **CVE-2026-31843** (GHSA-m5wg-cjgh-223j) — the
unauthenticated remote code execution via the code editor — and a set of related
hardening fixes; adds the Uzum Bank gateway. **Major** version because the
control-panel routes now require authentication by default (see Breaking).

### Security

- **[Critical] Fixed unauthenticated remote code execution in the code "editors"
  (CVE-2026-31843 / GHSA-m5wg-cjgh-223j).**
  `POST /payment/api/editable/update` (`ApiController::file_put`) wrote
  attacker-controlled content into `app/Http/Controllers/Payments/*.php`, which
  the package later `require`s — and the `file_name` was unsanitised (path
  traversal to any `.php` file). The writer now uses a strict filename allow-list
  plus realpath containment, and the control-panel routes require the `auth`
  middleware by default (see breaking change below).
- **[Critical] Constant-time signature / credential checks.** Click signature
  (`md5`) used a loose `==`, and Payme/Paynet/Oson credentials used a loose `!=` —
  both vulnerable to PHP type-juggling ("magic hash") and timing attacks. All now
  use `hash_equals()`.
- **[Critical] Paynet now verifies the callback amount** against the order amount
  in `PerformTransaction` (via `isProperModelAndAmount`); previously any amount was
  accepted and stored as paid.
- **[High] Removed the `APP_ENV == 'testing'` auth bypass** in the Payme driver.
  The Basic-auth check (and the test-only body/header seams) are now keyed on
  `app()->runningUnitTests()`, so a deployed app can no longer skip auth by having
  `APP_ENV=testing`.
- **[High] Fixed reflected XSS / header injection / open redirect** in the Stripe
  driver: the request-supplied redirect URL is validated (http/https only) and
  JSON-escaped before being emitted.
- **[High] Prevented double-perform race** in Payme `PerformTransaction` via an
  atomic compare-and-set on the transaction state, so the after-pay listener fires
  exactly once.
- **[Medium] Scoped the Paynet transaction lookup by `payment_system`** to avoid
  cross-gateway `system_transaction_id` collisions.
- **[Medium] Hardened Paynet SOAP XML parsing** against XXE / external-entity
  attacks (`LIBXML_NONET`, external entity loader disabled on PHP < 8).
- **[Low] Click `Complete` amount comparison** now compares integer tiyin instead
  of a loose `!=` on a float column.
- **[Low] Removed debug `echo` statements** that leaked validation internals in the
  Click gateway.
- **[Low] Updated the bundled jQuery 3.2.1 → 3.7.1** (CVE-2019-11358 et al.).
- **[Dev] Bumped `phpunit/phpunit` to `^9.6.33`** (drops the vulnerable `^7.0`
  range — GHSA-vvj3-c3rp-c85p / CVE-2026-24765).

### Added

- **Uzum Bank gateway** (Merchant API / Model A): server-to-server `check`,
  `create`, `confirm`, `reverse`, `status` operations with HTTP Basic + `serviceId`
  authentication and tiyin amounts. Driver `uzum`; see the README for routing and
  configuration.

### Fixed

- **`DataFormat::timestamp2datetime()` returned `1970-01-01` for numeric timestamps.**
  It applied `strtotime()` to the numeric Unix timestamp (which returns `false`),
  so any numeric input collapsed to the epoch. It now accepts both a numeric Unix
  timestamp (used by Uzcard) and a date-time string (Click `sign_time`, Paynet
  `transactionTime`). `datetime2timestamp()` is hardened symmetrically. (Addresses
  the bug behind #68; the `getReport` part of #68 was already fixed by #69.)
- **Octane / RoadRunner request body (#71).** All gateways now read the request
  body via `request()->getContent()` instead of `file_get_contents('php://input')`,
  which is unreliable under Octane/RoadRunner.
- **Config key mismatch.** Package config was merged under `pay-uz` but read as
  `config('payuz')` (and published as `payuz.php`), so defaults never applied. All
  reads and the merge key are now `payuz`.
- Modernised `phpunit.xml` to the PHPUnit 9.6 schema (`<coverage>` / `<logging>`).

### Breaking

- **Control-panel routes now default to the `['web', 'auth']` middleware.** If you
  published `config/payuz.php`, set `control_panel.middleware` to your own
  admin/authorization guard (the editor can write executable PHP and must be
  protected). The old default exposed it with only `web`.

## 1.0.0 - 201X-XX-XX

- initial release
