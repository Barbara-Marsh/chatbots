<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::group(['prefix' => 'vline'], function () {
    Route::get('/', 'Vline\VlineController@getAllStations')
        ->name('home');
    Route::get("privacy-policy", function () {
        return view('Vline.GoogleAssistantBot.privacy-policy');
    })->name('privacy-policy');
    Route::post("webhook", 'Vline\VlineController@googleWebhook');
    //Route::get('routes', 'Vline\VlineController@getAllRoutes');
});

Route::group(['prefix' => 'metro'], function () {
    Route::get('/', function () {
        return view('');
    });
});

//Route::group(['prefix' => 'facebook'], function () {
//    Route::group(['prefix' => '/twitter'], function () {
//        Route::get('/', function () {
//            $trends = Twitter::getTrendsAvailable();
//            $locations = [];
//            foreach ($trends as $trend) {
//                $locations[mb_strtolower($trend->name)] = $trend->woeid;
//            }
//            ksort($locations);
//
//            return view('Facebook.twitter')->with(['locations' => $locations]);
//        });
//        Route::get('/webhook', 'MessengerController@webhook');
//        Route::post('/webhook', 'MessengerController@webhook_post');
//    });
//});
