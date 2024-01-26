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
        $payme = PaymentSystem::firstOrCreate([
            'name'      => 'Payme',
            'system'    => 'payme'
        ]);
        $click = PaymentSystem::firstOrCreate([
            'name'      => 'Click',
            'system'    => 'click'
        ]);
        $paynet = PaymentSystem::firstOrCreate([
            'name'      => 'Paynet',
            'system'    => 'paynet'
        ]);
        $stripe = PaymentSystem::firstOrCreate([
            'name'      => 'Stripe',
            'system'    => 'stripe'
        ]);
        if (Schema::hasTable('payment_system_params')) {
            //Paycom
            PaymentSystemParam::firstOrCreate([
                'payment_system_id'    => $payme->id,
                'label'     => 'Login',
                'name'      => 'login',
                'value'     => 'Paycom'
            ]);
            PaymentSystemParam::firstOrCreate([
                'payment_system_id'    => $payme->id,
                'label'     => 'Merchant id',
                'name'      => 'merchant_id',
                'value'     => 'merchant'
            ]);
            PaymentSystemParam::firstOrCreate([
                'payment_system_id'    => $payme->id,
                'label'     => 'Password',
                'name'      => 'password',
                'value'     => 'password'
            ]);
            PaymentSystemParam::firstOrCreate([
                'payment_system_id'    => $payme->id,
                'label'     => 'Key',
                'name'      => 'key',
                'value'     => 'key'
            ]);
            //Click
            PaymentSystemParam::firstOrCreate([
                'payment_system_id'    => $click->id,
                'label'     => 'Service id',
                'name'      => 'service_id',
                'value'     => 'service_id'
            ]);
            PaymentSystemParam::firstOrCreate([
                'payment_system_id'    => $click->id,
                'label'     => 'Secret key',
                'name'      => 'secret_key',
                'value'     => 'key'
            ]);
            PaymentSystemParam::firstOrCreate([
                'payment_system_id'    => $click->id,
                'label'     => 'Merchant Id',
                'name'      => 'merchant_id',
                'value'     => '0000'
            ]);
            PaymentSystemParam::firstOrCreate([
                'payment_system_id'    => $click->id,
                'label'     => 'Merchant user id',
                'name'      => 'merchant_user_id',
                'value'     => '0000'
            ]);

            //Paynet
            PaymentSystemParam::firstOrCreate([
                'payment_system_id'    => $paynet->id,
                'label'     => 'Login',
                'name'      => 'login',
                'value'     => 'login'
            ]);
            PaymentSystemParam::firstOrCreate([
                'payment_system_id'    => $paynet->id,
                'label'     => 'Password',
                'name'      => 'password',
                'value'     => 'password'
            ]);
            PaymentSystemParam::firstOrCreate([
                'payment_system_id'    => $paynet->id,
                'label'     => 'Service id',
                'name'      => 'service_id',
                'value'     => 'service_id'
            ]);
            
            PaymentSystemParam::firstOrCreate([
                'payment_system_id'    => $stripe->id,
                'label'     => 'Secret key',
                'name'      => 'secret_key',
                'value'     => 'secret_key'
            ]);
            
            PaymentSystemParam::firstOrCreate([
                'payment_system_id'    => $stripe->id,
                'label'     => 'Publishable key',
                'name'      => 'publishable_key',
                'value'     => 'publishable_key'
            ]);
            PaymentSystemParam::firstOrCreate([
                'payment_system_id'    => $stripe->id,
                'label'     => 'Proxy',
                'name'      => 'proxy',
                'value'     => ''
            ]);
        }
    }
}
