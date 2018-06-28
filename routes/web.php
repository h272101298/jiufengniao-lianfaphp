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
//Route::options($uri, $callback);
Route::options('{all}',function (){return jsonResponse(['msg'=>'ok']);})->middleware('cross');
//Route::options('/{all}',function (){return jsonResponse(['msg'=>'ok']);})->middleware('cross');
//Route::options('v1/{all}',function (){jsonResponse(['msg'=>'ok']);})->middleware('cross');
Route::get('test',function (){
    setStoreId(1);
});
Route::get('test2',function (){
    dd(getStoreId()) ;
});
Route::group(['prefix'=>'v1','middleware'=>'cross'],function (){


    Route::get('delete/advert','V1\AdvertController@delAdvert');
//    Route::options('{all}',function (){return 'ok';})->middleware('cross');
    Route::post('login','V1\UserController@login');

    Route::get('product/types','V1\ProductController@getProductTypes');
    Route::group(['middleware'=>'auth'],function (){
        Route::get('adverts','V1\AdvertController@getAdverts');
        Route::post('advert','V1\AdvertController@addAdvert');
        Route::delete('advert','V1\AdvertController@delAdvert');
        Route::post('store/category','V1\StoreController@addStoreCategory');
        Route::get('store/categories','V1\StoreController@getStoreCategories');
        Route::delete('store/category','V1\StoreController@deletesStoreCategory');
        Route::get('settle/applies','V1\StoreController@getSettleApplies');
        Route::post('check/settle/apply','V1\StoreController@checkSettleApply');
        Route::post('product/type','V1\ProductController@createProductType');
        Route::delete('product/type','V1\ProductController@delProductType');
        Route::get('documents','V1\SystemController@getDocuments');
        Route::post('document','V1\SystemController@createDocument');
        Route::delete('document','V1\SystemController@delDocument');
        Route::post('product/category','V1\ProductController@addProductCategory');
        Route::get('product/categories','V1\ProductController@getProductCategories');
        Route::delete('product/category','V1\ProductController@delProductCategory');
        Route::post('store','V1\StoreController@addStore');
        Route::post('express','V1\StoreController@addExpress');
        Route::get('expresses','V1\StoreController@getStoreExpresses');
        Route::delete('express','V1\StoreController@delExpress');
    });

//    Route::post('login1',function (){
//        return 'DD';
//    });
});
