<?php

ini_set('display_errors', 'On');

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::group(['prefix' => '/user'], function() {
	Route::post('/clientLogin','UserController@clientlogin');
	Route::post('/createStaff','UserController@createStaff');
	Route::post('/updateStaff','UserController@updateStaff');
});

Route::group(['prefix' => '/alert'], function() {
	Route::post('/create','TaskController@alertCreate');
	Route::post('/searchExpress','ParcelCaseController@searchExpress');
});

Route::group(['prefix' => '/task'], function() {
	Route::post('/finish','TaskController@taskfinish');
	Route::post('/start/migrate','TaskController@migratelocker');
	Route::post('/box/mouth/update','MouthCaseController@boxmouthupdate');
	Route::post('/box/remoteUpdate','TaskController@remoteUpdateBox');
	Route::post('/box/forceInit','TaskController@forceInitBox');
	Route::post('/express/resetExpress','TaskController@resetExpress');
	Route::post('/mouth/remoteUnlock','TaskController@remoteUnlock');
	Route::post('/express/resendSMS','SMSCaseController@resendSMS');
	Route::post('/sms/history','SMSCaseController@smshistory');
	Route::post('/mouth/remoteUnlockByExpress','TaskController@remoteUnlockByExpress');
	Route::post('/resycnExpressByTime','ParcelCaseController@resycnExpressByTime');
	Route::post('/remoteReboot','TaskController@remoteReboot');
	Route::post('/forceResyncAll','TaskController@forceresyncall');
	Route::post('/remoteCommand','TaskController@remoteCommand');
	Route::any('/check/{imported}','TaskController@pingPong');
});

Route::group(['prefix' => '/box'], function() {
	Route::post('/mouth/sync','MouthCaseController@mouthsync');
	Route::get('/mouth/status','MouthCaseController@mouthstatus');
	Route::get('/init','BoxController@boxinit');
	Route::any('/pull','BoxController@boxpull');
	Route::get('/info/{imported}','BoxController@boxinfo');
	Route::get('/details','BoxController@boxdetails');
	Route::post('/finish','BoxController@boxfinish');
	Route::post('/guiInfo','BoxController@guiInfo');
	Route::post('/tvcLog','BoxController@tvcLog');
	Route::post('/tvcList','BoxController@tvcList');
	Route::post('/activityLog','BoxController@activityLog');
});

Route::group(['prefix' => '/express'], function() {
	Route::post('/staffTakeUserRejectExpress','ParceloutController@takuserreject');
	Route::post('/customerTakeExpress','ParceloutController@customertakexpress');
	Route::post('/staffTakeUserSendExpress','ParceloutController@takeuserexpress');
	Route::post('/staffTakeOverdueExpress','ParceloutController@takeoverduexpress');

	Route::post('/rejectExpressNotImported','ParcelinController@rejectexpress');
	Route::post('/staffStoreExpress','ParcelinController@staffstorexpress');
	Route::post('/customerStoreExpress','ParcelinController@customerstorexpress');
	
	Route::get('/imported/{imported}','ParcelCaseController@importedexpress');
	Route::get('/reject/checkRule/{imported}','ParcelCaseController@rejectcheckrule');
	Route::get('/customerExpress/{imported}','ParcelCaseController@customerexpress');
	Route::post('/syncExpress','ParcelCaseController@syncexpress');
	Route::post('/staffImportCustomerStoreExpress','ParcelCaseController@importcustomerstore');
	Route::post('/import','ParcelCaseController@importcourierstore');
	Route::post('/modifyPhoneNumber','ParcelCaseController@modifyPhoneNumber');
	Route::post('/deleteImportedExpress/{imported}','ParcelCaseController@deleteImportedExpress');
	Route::any('/query','ParcelCaseController@query');
	Route::any('/queryImported','ParcelCaseController@queryImported');

});
