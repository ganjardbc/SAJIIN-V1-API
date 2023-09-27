<?php

use Illuminate\Support\Facades\Route;

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

// Route::get('{any}', function () {
//     return view('app');
// })->where('any', '.*');

// Auth::routes();

Auth::routes();

Route::get('/', function () {
    return view('app');
});

Route::get('/order/download/{shop_id}/{start_date}/{end_date}/{order_status}/{payment_status}', 'OrderController@downloadReport')->name('downloadReport');
