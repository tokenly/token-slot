<?php


Route::get('/', 'WelcomeController@index');

/* API methods */
Route::get('api/v1/request/{slotId}', 'API\RequestController@get');
