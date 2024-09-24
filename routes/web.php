<?php


use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/cache/keys', function () {
    // Get all keys from Redis
    //    $keys = Cache::remember('key2' , 60 , function () {
    //        return 'Test';
    //    });
    // Get all keys from Redis
    //  $keys = Cache::store('redis')->keys('*');
    //   $keys = Redis::keys('*');

    $value = Cache::get('appointments_for_client_4');

    // Get ll Keys in the Cache Memory
    $keys = Cache::connection()->keys('*');

    // Return the keys as a JSON response
    return response()->json($keys);
});
