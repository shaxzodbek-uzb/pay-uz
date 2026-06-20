<?php

/*
 * You can place your custom package configuration in here.
 */
return [

    // Assets folder published folder name.

    'pay_assets_path' => '/vendor/pay-uz',
    'control_panel' => [
        // Middleware applied to the control-panel routes (dashboard, settings,
        // transactions, payment-systems CRUD and — most importantly — the code
        // "editors" that can write executable PHP files).
        //
        // SECURITY: these routes must be protected. The default ships with 'auth'
        // because the editor endpoint (POST /payment/api/editable/update) writes
        // PHP that the application later executes — exposing it unauthenticated is a
        // remote code execution hole. Override with your own admin/authorization guard.
        //
        // Value types: array, string, or null. 'web' is added automatically.
        'middleware' => ['web', 'auth'],
    ],
    'multi_transaction' => true,
];
