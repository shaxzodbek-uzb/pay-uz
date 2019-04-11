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
                'label'     => 'password',
                'name'      => 'password',
                'value'     => 'password'
            ]);
            PaymentSystemParam::firstOrCreate([
                'system'    => 'paynet',
                'label'     => 'service_id',
                'name'      => 'service_id',
                'value'     => 'service_id'
            ]);
        }
    }
}
