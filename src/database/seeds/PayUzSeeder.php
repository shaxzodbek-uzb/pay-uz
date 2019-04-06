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
            
        }
        if (Schema::hasTable('payment_system_params')) {
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
        }
    }
}
