<?php

namespace Goodone\PayUz\Tests\Features;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\Facades\Input;
use Goodoneuz\PayUz\Models\PaymentSystem;
use Goodoneuz\PayUz\Services\PaymentSystemService;

class Payme extends \Illuminate\Foundation\Testing\TestCase
{
    private $params;

    public function createApplication()
    {
        $app = require './bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        return $app;
    }

    public function setUp(): void
    {
      parent::setUp();
     $this->params = PaymentSystemService::getPaymentSystemParamsCollect(PaymentSystem::PAYME);
    }

    /** @test */
    public function true_is_true()
    {
        $this->CheckPerformTransaction();
         $this->CreateTransaction();

        $this->assertTrue(true);
    }

    /**
     * A basic functional test example.
     *
     * @return void
     */
    public function CheckPerformTransaction()
    {
        if(!isset($this->params['login']) || !isset($this->params['password']))
        {
            return false;
        }

        $str = '{
            "method" : "CheckPerformTransaction",
            "params" : {
                "amount" : 500000,
                "account" : {
                    "key" : 5
                }
            }
        }';

        $headers = [
            'Content-Type'  => 'text/json; charset=UTF-8',
            'HTTP-Authorization' => 'Basic ' . base64_decode($this->params['login'] . ':' .$this->params['password'])
        ];
        $response = $this->withHeaders($headers)->json('POST','/handle/payme',['request' => $str]);
        // dd($response);
        $response
            ->assertStatus(200)
            ->assertExactJson(json_decode('{"jsonrpc":"2.0","id":null,"result":{"allow":true},"error":null}',true));
    }

    public function CreateTransaction()
    {
        $str = '{
                "method" : "CreateTransaction",
                "params" : {
                "id" : "5305e3bab097f420a62ced0b",
                "time" : 1399114284039,
                "amount" : 500000,
                "account" : {
                    "key" : "5"
                }
            }
        }';
        $headers = [
            'Content-Type'  => 'text/json; charset=UTF-8',
        ];
        $response = $this->withHeaders($headers)->json('POST','/handle/payme',['request' => $str]);
        dd($response);
        $response
            ->assertStatus(200)
            ->assertExactJson(json_decode('{
                "result" : {
                    "create_time" : 1399114284039,
                    "transaction" : "5123",
                    "state" : 1,
                    "receivers" : null
                }
            }',true));
    }
}
