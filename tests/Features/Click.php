<?php

namespace Goodone\PayUz\Tests\Features;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\Facades\Input;
use Goodoneuz\PayUz\Models\PaymentSystem;
use Goodoneuz\PayUz\Services\PaymentSystemService;

class Click extends \Illuminate\Foundation\Testing\TestCase
{
    private $params;

    public function createApplication()
    {
        $app = require './bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->params = PaymentSystemService::getPaymentSystemParamsCollect(PaymentSystem::CLICK);
    }

    /** @test */
    public function true_is_true()
    {
        $this->Prepare();
        $this->assertTrue(true);
    }

    public function Prepare()
    {
        if (is_null($this->params['service_id']) || is_null($this->params['secret_key']))

        $arr = [];
        $arr['click_trans_id'] = rand(999,99999);
        $arr['service_id'] = $this->params['service_id'];
        $arr['click_paydoc_id'] = rand(11,99999);
        $arr['merchant_trans_id'] = 4;
        $arr['amount'] = 1000;
        $arr['action'] = 0;
        $arr['error'] = 0;
        $arr['error_note'] = 'error not';
        $arr['sign_time'] = date("Y-m-d h:m:s");

        $arr['sign_string'] = md5(
            $arr['click_trans_id'] .
                $arr['service_id'] .
                $this->params['secret_key'] .
                $arr['merchant_trans_id'] .
                $arr['amount'] .
                $arr['action'] .
                $arr['sign_time']
        );

        $response = $this->call('post','/handle/click',$arr);
        $res_content = json_decode(json_decode($response->getContent()));

        $arr['merchant_prepare_id'] = $res_content->merchant_prepare_id;
        $arr['sign_time'] = date("Y-m-d h:m:s");
        $arr['action'] = 1;

        $arr['sign_string'] = md5(
            $arr['click_trans_id'] .
            $arr['service_id'] .
            $this->params['secret_key'] .
            $arr['merchant_trans_id'] .
            $arr['merchant_prepare_id'] .
            $arr['amount'] .
            $arr['action'] .
            $arr['sign_time']
        );

        $response = $this->call('post','/handle/click',$arr);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
