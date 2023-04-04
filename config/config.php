<?php

/*
 * You can place your custom package configuration in here.
 */
return [

    // Assets folder published folder name.

    'pay_assets_path' => '/vendor/pay-uz',
    'control_panel' => [
        //middleware value types: array, string, null
        //'web' is optional if middleware is empty or null it will be added automatically
        'middleware' => null,
    ],
    'multi_transaction' => true,
];
