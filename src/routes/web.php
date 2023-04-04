<?php

$middleware = config('payuz')['control_panel']['middleware'];

//checking array is empty or null
if (empty($middleware) || $middleware == null) {
    $middleware = ['web'];
} else {
    //converting string to array if it is string
    if (is_string($middleware)) {
        $middleware = [$middleware];
    }

    //checking for 'web' element in the array
    $key = array_search('web', $middleware);
    if ($key !== false) {
        //remove 'web' from the array
        unset($middleware[$key]);

        //add 'web' to the beginning of the array
        array_unshift($middleware, 'web');
    }
}

Route::middleware($middleware)->name('payment.')->prefix('payment')->namespace('Goodoneuz\PayUz\Http\Controllers')->group(function () {
    Route::any('dashboard', 'PageController@dashboard')->name('dashboard');
    Route::any('editors', 'PageController@editors')->name('editors');
    Route::any('blank', 'PageController@blank')->name('blank');
    Route::any('settings', 'PageController@settings')->name('settings');
    Route::get('payment_params/delete/{param_id}', 'PaymentSystemController@deleteParam')->name('payment_systems.delete_param');
    Route::get('payment_systems/edit/status/{payment_system}', 'PaymentSystemController@editStatus')->name('payment_systems.edit_status');

    // --editable functions
    Route::any('/api/editable/update', 'ApiController@file_put')->name('api.file_put');
    // end --editable functions

    Route::resource('transactions', 'TransactionController');
    Route::resource('projects', 'ProjectController');
    Route::resource('payment_systems', 'PaymentSystemController');
    Route::resource('transactions', 'TransactionController');
});
