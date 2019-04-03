<?php

Route::middleware('web')->name('payment.')->prefix('payment')->namespace('Goodoneuz\PayUz\Http\Controllers')->group(function() {
    Route::any('dashboard','PageController@dashboard')->name('dashboard');
    Route::any('editors','PageController@editors')->name('editors');
    Route::any('blank','PageController@blank')->name('blank');
    Route::any('settings','PageController@settings')->name('settings');
    Route::resource('transactions','TransactionController');
    Route::resource('payment_systems','PaymentSystemController');
    Route::get('payment_params/delete/{param_id}','PaymentSystemController@deleteParam')->name('payment_systems.delete_param');
    Route::get('payment_systems/edit/status/{payment_system}','PaymentSystemController@editStatus')->name('payment_systems.edit_status');
    Route::resource('transactions','TransactionController');
});
