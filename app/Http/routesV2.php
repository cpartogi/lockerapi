<?php

Route::group(['prefix'=>'payment'],function (){
    Route::post('getAvailableMethod','PaymentV2Controller@getAvailableMethod');
    Route::post('checkPayment','PaymentV2Controller@checkPayment');
    Route::post('createPayment','PaymentV2Controller@createPayment');
    Route::post('getListPayment','PaymentV2Controller@getListPayment');
    Route::post('callbackFixed','PaymentV2Controller@callbackFixedPayment');
});

Route::group(['prefix'=>'member'],function (){
   Route::post('fcmToken','MemberController@fcmToken');
});