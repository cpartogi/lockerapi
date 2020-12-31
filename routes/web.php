<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

 // Route::get('/', function () {
 //     return view('welcome');
 // });

Route::get('/', [
	'as' => 'landing-page', 'uses' => 'PopSendController@index'
]);	

Route::get('/popsend', [
    'as' => 'popsend', 'uses' => 'PopSendController@popsend'
]);
 
Route::get('/nearest/{lat}/{long}', [
    'as' => 'topup', 'uses' => 'PopSendController@nearest'
]);	

  
 Route::group(['prefix' => 'topup'], function () {
	Route::get('/', [
	    'as' => 'topup', 'uses' => 'PopSendController@topup'
	]);	

	Route::get('/process', [
	    'as' => 'topup-process', 'uses' => 'PopSendController@topupProcess'
	]);			
	 	
});
	

