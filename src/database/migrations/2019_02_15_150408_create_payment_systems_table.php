<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentSystemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_systems', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('system');
            $table->string('merchant_id')->nullable();
            $table->string('service_id')->nullable();
            $table->string('secret_key')->nullable();
            $table->string('merchant_user_id')->nullable();
            $table->string('login')->nullable();
            $table->string('password')->nullable();
            $table->string('end_point_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_systems');
    }
}
