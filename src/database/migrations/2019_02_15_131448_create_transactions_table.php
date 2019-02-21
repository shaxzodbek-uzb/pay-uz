<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('payment_system');
            $table->string('system_transaction_id');
            $table->double('amount',15,5);
            $table->integer('currency_code');
            $table->string('payable')->nullable();
            $table->integer('payable_id')->nullable();
            $table->integer('state');
            $table->dateTime('create_time')->nullable();
            $table->dateTime('cancel_time')->nullable();
            $table->dateTime('perform_time')->nullable();
            $table->dateTime('system_time_datetime')->nullable();
            $table->string('comment')->nullable();
            $table->string('detail')->nullable();
            $table->softDeletes();
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
        Schema::dropIfExists('transactions');
    }
}
