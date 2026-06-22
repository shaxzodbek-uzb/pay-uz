<?php

/*
 * You can place your custom package configuration in here.
 */
return [

    // Assets folder published folder name.

    'pay_assets_path' => '/vendor/pay-uz',
    'control_panel' => [
        // Middleware applied to the control-panel routes (dashboard, settings,
        // transactions and payment-systems CRUD).
        //
        // SECURITY: these routes expose payment configuration and must be
        // protected. The default ships with 'auth'; override with your own
        // admin/authorization guard.
        //
        // Value types: array, string, or null. 'web' is added automatically.
        'middleware' => ['web', 'auth'],
    ],
    'multi_transaction' => true,

    /*
     * Payment hooks.
     *
     * The gateways bridge to your application through a PaymentResolver: it maps
     * your payable model <-> the key sent to the payment system, validates
     * callback amounts and (optionally) post-processes responses. This replaces
     * the old runtime-writable app/Http/Controllers/Payments/*.php hook files.
     *
     * Point this at your own implementation of
     * Goodoneuz\PayUz\Payments\Contracts\PaymentResolver. The shipped default
     * resolves models from App\Models\User (matching the old default hooks).
     *
     * Lifecycle hooks (before-pay / paying / after-pay / cancel-pay) are Laravel
     * events now — subscribe to Goodoneuz\PayUz\Payments\Events\* instead.
     */
    'payments' => [
        'resolver' => \Goodoneuz\PayUz\Payments\DefaultPaymentResolver::class,
    ],

    /*
     * OFD fiscalization (онлайн-ККМ / virtual kassa).
     *
     * PKM No. 943 (23.11.2019) requires every settlement with the public to be
     * registered with a Fiscal Data Operator. `default` selects the driver used
     * by the Fiscalizer facade; `drivers` holds per-driver credentials. The
     * shipped default is 'null' (a no-op that validates the receipt and returns a
     * synthetic sign) so a fresh install never blocks on credentials it lacks —
     * switch to a real driver in production.
     */
    'fiscalization' => [
        'default' => env('PAYUZ_FISCAL_DRIVER', 'null'), // 'null' | 'ofd'

        // Default VAT rate applied to receipt items that do not set their own.
        'vat_percent' => env('PAYUZ_FISCAL_VAT', 12),

        'drivers' => [
            // Generic OFD / virtual-kassa HTTP gateway (soliq fiscal-receipt shape).
            // Point `endpoint` at your provider's register-receipt URL.
            'ofd' => [
                'endpoint'    => env('OFD_ENDPOINT'),
                'token'       => env('OFD_TOKEN'),
                'terminal_id' => env('OFD_TERMINAL_ID'),
            ],
            'null' => [
                'log' => env('PAYUZ_FISCAL_LOG', false),
            ],
        ],
    ],

    /*
     * Card tokenization + recurring charges (the `Subscribe` facade).
     *
     * `default` selects the driver; the shipped default is 'null' (a no-op that
     * simulates the happy path) so a fresh install never blocks on credentials.
     * Switch to 'payme' (Payme Subscribe API) in production. NOTE: the `key` is
     * the secret X-Auth payment key from the Payme cabinet — required to charge
     * receipts — and must NEVER be exposed to the browser.
     */
    'subscribe' => [
        'default' => env('PAYUZ_SUBSCRIBE_DRIVER', 'null'), // 'null' | 'payme' | 'atmos'

        'drivers' => [
            'payme' => [
                'merchant_id' => env('PAYME_SUBSCRIBE_MERCHANT_ID', env('PAYME_MERCHANT_ID')),
                'key'         => env('PAYME_SUBSCRIBE_KEY'),
                'test'        => env('PAYME_SUBSCRIBE_TEST', false),
            ],
            // ATMOS — card vault + OTP over OAuth2. Amounts are tiyin (no
            // conversion). `api_key` is used only for callback signatures.
            'atmos' => [
                'consumer_key'    => env('ATMOS_CONSUMER_KEY'),
                'consumer_secret' => env('ATMOS_CONSUMER_SECRET'),
                'store_id'        => env('ATMOS_STORE_ID'),
                'api_key'         => env('ATMOS_API_KEY'),
                'terminal_id'     => env('ATMOS_TERMINAL_ID'),
                'lang'            => env('ATMOS_LANG', 'uz'),
                'test'            => env('ATMOS_TEST', false),
            ],
            'null' => [],
        ],
    ],

    /*
     * Card-acquiring aggregators (the `Checkout` facade) — hosted checkout +
     * card-on-file + capture/refund/webhook over one REST integration (Uzcard,
     * Humo, Visa, Mastercard, international). The shipped default is 'null' (a
     * simulator); switch to 'octo' in production.
     */
    'checkout' => [
        'default' => env('PAYUZ_CHECKOUT_DRIVER', 'null'), // 'null' | 'octo'

        'drivers' => [
            'octo' => [
                'shop_id'    => env('OCTO_SHOP_ID'),
                'secret'     => env('OCTO_SECRET'),
                // Secret used to verify inbound webhook signatures (issued by Octo,
                // may differ from `secret`). Webhooks are rejected without it.
                'unique_key' => env('OCTO_UNIQUE_KEY'),
                'test'       => env('OCTO_TEST', false),
                'return_url' => env('OCTO_RETURN_URL'),
                'notify_url' => env('OCTO_NOTIFY_URL'),
                'language'   => env('OCTO_LANGUAGE', 'uz'),
            ],
            // Multicard — amounts are tiyin (no conversion). `base_url` is the
            // prod/sandbox switch (https://mesh.multicard.uz | https://dev-mesh.multicard.uz).
            'multicard' => [
                'base_url'        => env('MULTICARD_BASE_URL', 'https://mesh.multicard.uz'),
                'application_id'  => env('MULTICARD_APPLICATION_ID'),
                'secret'          => env('MULTICARD_SECRET'),
                'store_id'        => env('MULTICARD_STORE_ID'),
                'callback_url'    => env('MULTICARD_CALLBACK_URL'),
                'language'        => env('MULTICARD_LANGUAGE', 'uz'),
                'callback_scheme' => env('MULTICARD_CALLBACK_SCHEME', 'webhooks'), // 'webhooks' | 'success'
            ],
            'null' => [],
        ],
    ],

    /*
     * BNPL / installments (the `Bnpl` facade) — buy-now-pay-later credit
     * contracts (eligibility → calculate tariffs → create contract → confirm).
     * Uzum Nasiya is the first driver; its partner Bearer JWT is issued at
     * onboarding (no self-serve key, no published sandbox). The shipped default
     * is 'null' (a simulator) so a fresh install works offline.
     */
    'bnpl' => [
        'default' => env('PAYUZ_BNPL_DRIVER', 'null'), // 'null' | 'uzum_nasiya'

        'drivers' => [
            'uzum_nasiya' => [
                'base_url' => env('UZUM_NASIYA_BASE_URL', 'https://merchants-api.uzumnasiya.uz'),
                'token'    => env('UZUM_NASIYA_TOKEN'),                 // partner Bearer JWT
                'otp_mode' => env('UZUM_NASIYA_OTP_MODE', 'webview'),   // 'webview' | 'sms'
            ],
            'null' => [],
        ],
    ],

    /*
     * E-invoicing / e-documents (the `Einvoice` facade) — ЭСФ (electronic invoices)
     * and related documents via an operator (Didox). The package never signs:
     * E-IMZO PKCS#7 is supplied by the host app through a Signer (or a pre-signed
     * blob). The shipped default is 'null' (a simulator). Amounts are tiyin in your
     * code; the driver emits decimal-som on the wire.
     */
    'einvoice' => [
        'default' => env('PAYUZ_EINVOICE_DRIVER', 'null'), // 'null' | 'didox'

        'drivers' => [
            'didox' => [
                'base_url'       => env('DIDOX_BASE_URL', 'https://api-partners.didox.uz'), // sandbox: https://testapi3.didox.uz
                'partner_token'  => env('DIDOX_PARTNER_TOKEN'),
                'partner_header' => env('DIDOX_PARTNER_HEADER', 'Partner-Authorization'),
                'user_key'       => env('DIDOX_USER_KEY'),   // or obtain via login()
                'locale'         => env('DIDOX_LOCALE', 'ru'),
            ],
            'null' => [],
        ],
    ],
];
