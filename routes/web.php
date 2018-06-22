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

//Route::options()
//Route::get('/', function () {
//    return view('welcome');
//});
//Route::get('test',function (){
//    $message = getRequestMessage('usernameRequired');
//    dd($message);
////    $user->create($data);
//});
Route::options('{all}',function (){return jsonResponse(['msg'=>'ok']);})->middleware('cross');
Route::options('v1/{all}',function (){jsonResponse(['msg'=>'ok']);})->middleware('cross');
Route::group(['prefix'=>'v1'],function (){

    Route::get('delete/advert','V1\AdvertController@delAdvert');
//    Route::options('{all}',function (){return 'ok';})->middleware('cross');
    Route::post('login','V1\UserController@login');
    Route::group(['middleware'=>'auth'],function (){
        Route::get('adverts','V1\AdvertController@getAdverts');
        Route::post('advert','V1\AdvertController@addAdvert');
        Route::delete('advert','V1\AdvertController@delAdvert');
        Route::post('store/category','V1\StoreController@addStoreCategory');
        Route::delete('store/category','V1\StoreController@deletesStoreCategory');
        Route::get('store/categories','V1\StoreController@getStoreCategories');
    });

//    Route::post('login1',function (){
//        return 'DD';
//    });
});
