<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});
Route::group(['prefix'=>'v1'],function (){
    Route::post('login','V1\WeChatController@login');
    Route::get('product/types','V1\ProductController@getProductTypesTree');
    Route::get('documents','V1\SystemController@getDocuments');
    Route::group(['middleware'=>'checkToken'],function (){
        Route::post('address','V1\WeChatController@createAddress');
        Route::get('addresses','V1\WeChatController@getAddresses');
        Route::get('address','V1\WeChatController@getAddress');
        Route::delete('address','V1\WeChatController@delAddress');
        Route::post('default/address','V1\WeChatController@setDefaultAddress');
        Route::post('store/apply','V1\WeChatController@createApply');
        Route::get('store/categories','V1\StoreController@getStoreCategories');
//        Route::get('','V1\ProductController@getProductTypes');
//        Route::get('product/')
    });

});
