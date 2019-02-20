<?php

Route::middleware('web')->name('payment.')->prefix('payment')->namespace('Goodoneuz\PayUz\Http\Controllers')->group(function() {
    Route::any('dashboard','PageController@dashboard')->name('dashboard');
    Route::any('editors','PageController@editors')->name('editors');
    Route::any('blank','PageController@blank')->name('blank');
    Route::resource('transactions','TransactionController');
    Route::any('/redirect/','PaymentProxy@redirect')->name('redirect');
    Route::any ('/{payment}/{type?}/{invoice_id?}/{status?}','PaymentProxy@handle')->name('handle');
});
