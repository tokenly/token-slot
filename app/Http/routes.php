<?php


Route::get('/', 'WelcomeController@index');

/* API methods */
Route::get('api/v1/payment/request/{slotId}', 'API\PaymentController@request');
Route::get('api/v1/payment/{paymentId}', 'API\PaymentController@get');

Route::get('api/v1/slots', 'API\SlotsController@all');
Route::get('api/v1/slots/{slotId}', 'API\SlotsController@get');
Route::get('api/v1/slots/{slotId}/payments', 'API\SlotsController@payments');

Route::post('hooks/payment', array('as' => 'hooks.payment', 'uses' => 'HookController@payment'));
