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
    $search = new \App\Libraries\ExpressSearch('1358357','4ca2f3ce-5025-4d03-af5a-60bb64e1ffd1');
    $data = $search->getOrderTracesByJson('LHT','753014072569');
    $data = json_decode($data);
    $data = $data->Traces;
    $data = array_reverse($data);
    var_dump($data);
});
Route::get('test2',function (){
    dd(getStoreId()) ;
});
Route::group(['prefix'=>'v1','middleware'=>'cross'],function (){


    Route::get('delete/advert','V1\AdvertController@delAdvert');
//    Route::options('{all}',function (){return 'ok';})->middleware('cross');
    Route::post('login','V1\UserController@login');
    Route::get('logout','V1\UserController@logout');
    Route::post('permission','V1\SystemController@addPermission');
    Route::get('permissions','V1\SystemController@getPermissions');
    Route::get('product/types','V1\ProductController@getProductTypes');
    Route::get('hot','V1\ProductController@addHot');
    Route::get('new','V1\ProductController@addNew');
    Route::get('offer','V1\ProductController@addOffer');
    Route::get('hot/type','V1\ProductController@addHotType');
    Route::get('orders','V1\OrderController@getOrders');
    Route::get('order','V1\OrderController@getOrder');
    Route::post('ship/order','V1\OrderController@shipOrder');
    Route::get('proxy/applies','V1\UserController@listProxyApply');
    Route::get('proxy/pass','V1\UserController@passProxyApply');
    Route::get('proxy/reject','V1\UserController@rejectProxyApply');
    Route::get('proxy/users','V1\UserController@getProxyList');
    Route::get('refuses','V1\OrderController@getRefuseList');
    Route::get('refuse','V1\OrderController@refuseOrder');
    Route::post('brokerage/ratio','V1\SystemController@addBrokerageRatio');
    Route::get('brokerage/ratio','V1\SystemController@getBrokerageRatio');
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
        Route::post('product','V1\ProductController@addProduct');
        Route::get('del/product','V1\ProductController@softDelProduct');
        Route::delete('product','V1\ProductController@delProduct');
        Route::get('check/product','V1\ProductController@checkProduct');
        Route::get('shelf/product','V1\ProductController@shelfProduct');
        Route::get('products','V1\ProductController@getProducts');
        Route::post('role','V1\SystemController@addRole');
        Route::get('roles','V1\SystemController@getRoles');
        Route::delete('role','V1\SystemController@delRole');
        Route::post('user','V1\UserController@addUser');
        Route::get('users','V1\UserController@getUsers');
        Route::get('withdraw/applies','V1\UserController@getWithdrawApplies');
        Route::get('withdraw/pass','V1\UserController@passWithdrawApply');
        Route::get('withdraw/reject','V1\UserController@rejectWithdrawApply');
        Route::post('express/config','V1\StoreController@addExpressConfig');
        Route::get('express/config','V1\StoreController@getExpressConfig');
        Route::get('wechat/users','V1\WeChatController@getWechatUsers');
        Route::post('tx/config','V1\SystemController@addTxConfig');
        Route::get('tx/config','V1\SystemController@getTxConfig');

//        Route::post('user','V1\UserController@addUser');
    });

//    Route::post('login1',function (){
//        return 'DD';
//    });
});
