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
Route::post('pay/notify','V1\OrderController@payNotify');
Route::post('member/notify','V2\MemberController@memberNotify');
Route::group(['prefix'=>'v1'],function (){
    Route::post('login','V1\WeChatController@login');
    Route::get('test','V1\WeChatController@test');
    Route::get('product/types','V1\ProductController@getProductTypesTree');
    Route::get('product/types/parents','V1\ProductController@getProductTypesParents');
    Route::get('product/types/tree','V1\ProductController@getProductTypesTreeByParent');
    Route::get('documents','V1\SystemController@getDocuments');
    Route::get('adverts','V1\AdvertController@getAdverts');
    Route::get('recommend/list','V1\ProductController@getRecommendList');
    Route::get('hot/types','V1\ProductController@getHotTypes');
    Route::get('poster/configs','V1\SystemController@getPosterConfigs');
    Route::get('icon/configs','V1\SystemController@getIconConfigs');
    Route::group(['middleware'=>'checkToken'],function (){
        Route::post('address','V1\WeChatController@createAddress');
        Route::get('addresses','V1\WeChatController@getAddresses');
        Route::get('address','V1\WeChatController@getAddress');
        Route::delete('address','V1\WeChatController@delAddress');
        Route::post('default/address','V1\WeChatController@setDefaultAddress');
        Route::get('default/address','V1\WeChatController@getDefaultAddress');
        Route::post('store/apply','V1\WeChatController@createApply');
        Route::get('store/categories','V1\StoreController@getStoreCategories');
        Route::get('products','V1\ProductController@getProductsApi');
        Route::get('product','V1\ProductController@getProductApi');
        Route::get('product/assesses','V1\ProductController@getProductAssesses');
        Route::get('stock','V1\ProductController@getStock');
        Route::post('cart','V1\ProductController@addCart');
        Route::get('carts','V1\ProductController@getCarts');
        Route::delete('carts','V1\ProductController@delCarts');
        Route::post('order','V1\OrderController@createOrder');
        Route::get('order/express','V1\OrderController@getOrderExpress');
        Route::get('order/confirm','V1\OrderController@confirmOrder');
        Route::post('order/assess','V1\OrderController@assessOrder');
        Route::get('order/cancel','V1\OrderController@cancelOrder');
        Route::get('orders','V1\OrderController@getMyOrders');
        Route::get('orders/count','V1\OrderController@countUserOrders');
        Route::post('pay','V1\OrderController@payOrder');
        Route::post('collect','V1\ProductController@addCollect');
        Route::get('collects','V1\ProductController@getCollects');
        Route::delete('collect','V1\ProductController@delCollect');
        Route::post('proxy/apply','V1\WeChatController@addProxyApply');
        Route::get('proxy/apply','V1\WeChatController@getProxyApply');
        Route::post('withdraw/apply','V1\WeChatController@addWithdrawApply');
        Route::get('withdraw/applies','V1\WeChatController@getWithdrawApplies');
        Route::get('user/amount','V1\WeChatController@getUserAmount');
        Route::get('user/qrcode','V1\WeChatController@getUserQrCode');
        Route::get('project/qrcode','V1\ProductController@getProductQrCode');
        Route::post('user/info','V1\WeChatController@addUserInfo');
        Route::get('user/info','V1\WeChatController@getUserInfo');
        Route::get('proxy/info','V1\WeChatController@getProxyInfo');
        Route::get('proxy/list','V1\WeChatController@getProxyList');
        Route::post('proxy/list','V1\WeChatController@addProxyList');
        Route::get('brokerages','V1\WeChatController@getBrokerageList');
        Route::post('notify/list','V1\WeChatController@addNotifyList');
    });

});
Route::group(['prefix'=>'v2'],function (){
    Route::get('card/promotions','V2\CardController@getEnablePromotions');
    Route::get('hot/card/promotions','V2\CardController@getHotCardPromotions');
    Route::get('bargain/promotions','V2\BargainController@getEnablePromotions');
    Route::get('bargain/stock','V2\BargainController@getBargainStock');
    Route::group(['middleware'=>'checkToken'],function (){
        Route::get('card/promotion','V2\CardController@getEnablePromotion');
        Route::get('card/draw','V2\CardController@drawCard');
        Route::post('card/gift','V2\CardController@giftCard');
        Route::get('promotions/count','V2\WeChatController@countPromotions');
        Route::get('member','V2\WeChatController@member');
        Route::get('card/records','V2\CardController@getCardJoinRecords');
        Route::get('bargain/promotion','V2\BargainController@getEnablePromotion');
        Route::post('bargain/list','V2\BargainController@addBargainList');
        Route::post('bargain','V2\BargainController@bargain');
        Route::get('bargain/records','V2\BargainController@getBargainRecords');
        Route::get('bargain/status','V2\BargainController@getBargainPrice');
        Route::get('my/bargain/promotions','V2\BargainController@getMyPromotions');
        Route::get('member/levels','V2\MemberController@getMemberLevels');
        Route::post('member/order','V2\MemberController@addMemberRecord');
    });
});
Route::group(['prefix'=>'v3'],function (){
    Route::get('group/buy/promotions','V3\GroupBuyController@getPromotions');
    Route::get('group/buy/promotion','V3\GroupBuyController@getPromotion');
    Route::get('group/buy/stock','V3\GroupBuyController@getGroupBuyStock');
    Route::post('group/buy/order','V3\OrderController@makeOrder');
    Route::post('order','V3\OrderController@createOrder');
    Route::get('group/buy/lists','V3\GroupBuyController@getOrderBuyList');
    Route::get('group/buy/list','V3\GroupBuyController@getGroupBuyList');
    Route::get('my/group/buy','V3\GroupBuyController@getMyGroupBuy');
    Route::get('my/group/free','V3\GroupBuyController@getUserGroupFree');
    Route::post('sign','V3\SignController@sign');
    Route::get('sign','V3\SignController@getSignRecords');
    Route::get('sign/configs','V3\SignController@getSignConfigs');
    Route::get('coupons','V3\CouponController@getStoreCoupons');
    Route::get('take/coupon','V3\CouponController@addUserCoupon');
    Route::get('my/coupons','V3\CouponController@myCoupons');
    Route::get('my/score','V3\UserController@getUserScore');
    Route::get('score/store','V3\UserController@getUserScore');
    Route::get('score/products','V3\ScoreController@getAllScoreProducts');
    Route::get('score/product','V3\ScoreController@getScoreProductApi');
    Route::get('score/product/stock','V3\ScoreController@getScoreProductStock');
    Route::post('score/order','V3\OrderController@createScoreOrder');
    Route::get('score/config','V3\ScoreController@getScoreConfig');
    Route::get('score/records','V3\ScoreController@getScoreRecords');
    Route::get('pickup/config','V3\SystemController@getPickUpConfig');
    Route::get('qrcode','V3\SystemController@makeQrcode');
    Route::post('prize','V3\PrizeController@prize');
    Route::get('prizes','V3\PrizeController@getPrizes');
    Route::post('share','V3\WeChatController@share');
});