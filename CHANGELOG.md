# Changelog

All notable changes to `pay-uz` will be documented in this file

## Unreleased

### Security

- **[Critical] Fixed unauthenticated remote code execution in the code "editors".**
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
