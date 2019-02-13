<?php

Route::middleware('web')->name('payment.')->prefix('payment')->namespace('Goodoneuz\PayUz\Http\Controllers')->group(function() {
	Route::any('/redirect/','PaymentProxy@redirect')->name('redirect');
	Route::any ('/{payment}/{type?}/{invoice_id?}/{status?}','PaymentProxy@handle')->name('handle');
});
