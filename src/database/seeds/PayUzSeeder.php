<?php

namespace Goodoneuz\PayUz\Database\Seeds;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Goodoneuz\PayUz\Models\PaymentSystem;
use Goodoneuz\PayUz\Models\PaymentSystemParam;

class PayUzSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (Schema::hasTable('payment_systems')) {
            PaymentSystem::firstOrCreate([
                'name'      => 'Payme',
                'system'    => 'payme'
            ]);
            PaymentSystem::firstOrCreate([
                'name'      => 'Click',
                'system'    => 'click'
            ]);
            PaymentSystem::firstOrCreate([
                'name'      => 'Paynet',
                'system'    => 'paynet'
            ]);
            PaymentSystem::firstOrCreate([
                'name'      => 'Stripe',
                'system'    => 'stripe'
            ]);
            PaymentSystem::firstOrCreate([
                'name'      => 'Uzum Bank',
                'system'    => 'uzum'
            ]);
            PaymentSystem::firstOrCreate([
                'name'      => 'Octo',
                'system'    => 'octo'
            ]);
        }
        if (Schema::hasTable('payment_system_params')) {
            //Paycom
            PaymentSystemParam::firstOrCreate([
                'system'    => 'payme',
                'label'     => 'Login',
                'name'      => 'login',
                'value'     => 'Paycom'
            ]);
            PaymentSystemParam::firstOrCreate([
                'system'    => 'payme',
                'label'     => 'Merchant id',
                'name'      => 'merchant_id',
                'value'     => 'merchant'
            ]);
            PaymentSystemParam::firstOrCreate([
                'system'    => 'payme',
                'label'     => 'Password',
                'name'      => 'password',
                'value'     => 'password'
            ]);
            PaymentSystemParam::firstOrCreate([
                'system'    => 'payme',
                'label'     => 'Key',
                'name'      => 'key',
                'value'     => 'key'
            ]);
            //Click
            PaymentSystemParam::firstOrCreate([
                'system'    => 'click',
                'label'     => 'Service id',
                'name'      => 'service_id',
                'value'     => 'service_id'
            ]);
            PaymentSystemParam::firstOrCreate([
                'system'    => 'click',
                'label'     => 'Secret key',
                'name'      => 'secret_key',
                'value'     => 'key'
            ]);
            PaymentSystemParam::firstOrCreate([
                'system'    => 'click',
                'label'     => 'Merchant Id',
                'name'      => 'merchant_id',
                'value'     => '0000'
            ]);
            PaymentSystemParam::firstOrCreate([
                'system'    => 'click',
                'label'     => 'Merchant user id',
                'name'      => 'merchant_user_id',
                'value'     => '0000'
            ]);

            //Paynet
            PaymentSystemParam::firstOrCreate([
                'system'    => 'paynet',
                'label'     => 'Login',
                'name'      => 'login',
                'value'     => 'login'
            ]);
            PaymentSystemParam::firstOrCreate([
                'system'    => 'paynet',
                'label'     => 'Password',
                'name'      => 'password',
                'value'     => 'password'
            ]);
            PaymentSystemParam::firstOrCreate([
                'system'    => 'paynet',
                'label'     => 'Service id',
                'name'      => 'service_id',
                'value'     => 'service_id'
            ]);
            
            PaymentSystemParam::firstOrCreate([
                'system'    => 'stripe',
                'label'     => 'Secret key',
                'name'      => 'secret_key',
                'value'     => 'secret_key'
            ]);
            
            PaymentSystemParam::firstOrCreate([
                'system'    => 'stripe',
                'label'     => 'Publishable key',
                'name'      => 'publishable_key',
                'value'     => 'publishable_key'
            ]);
            PaymentSystemParam::firstOrCreate([
                'system'    => 'stripe',
                'label'     => 'Proxy',
                'name'      => 'proxy',
                'value'     => ''
            ]);

            //Uzum Bank (Merchant API)
            PaymentSystemParam::firstOrCreate([
                'system'    => 'uzum',
                'label'     => 'Login',
                'name'      => 'login',
                'value'     => 'login'
            ]);
            PaymentSystemParam::firstOrCreate([
                'system'    => 'uzum',
                'label'     => 'Password',
                'name'      => 'password',
                'value'     => 'password'
            ]);
            PaymentSystemParam::firstOrCreate([
                'system'    => 'uzum',
                'label'     => 'Service id',
                'name'      => 'service_id',
                'value'     => 'service_id'
            ]);
            PaymentSystemParam::firstOrCreate([
                'system'    => 'uzum',
                'label'     => 'Account key (params field that identifies the order)',
                'name'      => 'key',
                'value'     => 'id'
            ]);

            // Octo (acquiring). Seeded blank rather than with placeholders: the
            // Checkout manager ignores empty params, so an unconfigured Octo falls
            // back to config/env instead of sending "shop_id" as a shop id.
            PaymentSystemParam::firstOrCreate([
                'system'    => 'octo',
                'label'     => 'Shop id',
                'name'      => 'shop_id',
                'value'     => ''
            ]);
            PaymentSystemParam::firstOrCreate([
                'system'    => 'octo',
                'label'     => 'Secret key',
                'name'      => 'secret',
                'value'     => ''
            ]);
            PaymentSystemParam::firstOrCreate([
                'system'    => 'octo',
                'label'     => 'Unique key (webhook signature)',
                'name'      => 'unique_key',
                'value'     => ''
            ]);
            PaymentSystemParam::firstOrCreate([
                'system'    => 'octo',
                'label'     => 'Test mode (1 / 0)',
                'name'      => 'test',
                'value'     => '1'
            ]);
            PaymentSystemParam::firstOrCreate([
                'system'    => 'octo',
                'label'     => 'Return URL (after payment)',
                'name'      => 'return_url',
                'value'     => ''
            ]);
            PaymentSystemParam::firstOrCreate([
                'system'    => 'octo',
                'label'     => 'Notify URL (payment webhook)',
                'name'      => 'notify_url',
                'value'     => ''
            ]);
            PaymentSystemParam::firstOrCreate([
                'system'    => 'octo',
                'label'     => 'Bind notify URL (card token callback)',
                'name'      => 'bind_notify_url',
                'value'     => ''
            ]);
            PaymentSystemParam::firstOrCreate([
                'system'    => 'octo',
                'label'     => 'Payment form language (oz / uz / en / ru)',
                'name'      => 'language',
                'value'     => 'uz'
            ]);
            PaymentSystemParam::firstOrCreate([
                'system'    => 'octo',
                'label'     => 'Receipt email (used when the customer has none)',
                'name'      => 'receipt_email',
                'value'     => ''
            ]);
            PaymentSystemParam::firstOrCreate([
                'system'    => 'octo',
                'label'     => 'Bindable card schemes (comma separated)',
                'name'      => 'bindable_methods',
                'value'     => 'uzcard,humo'
            ]);
        }
    }
}
