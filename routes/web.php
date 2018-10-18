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
    dd(\Illuminate\Support\Facades\Auth::check());
});
Route::post('test2','V1\SystemController@test');
Route::group(['prefix'=>'v1','middleware'=>'cross'],function (){
    Route::post('upload','V1\SystemController@upload');
    Route::post('login','V1\UserController@login');
    Route::get('logout','V1\UserController@logout');
    Route::group(['middleware'=>'auth'],function (){
        Route::get('adverts','V1\AdvertController@getAdverts')->middleware('permission:advertList');//获取首页轮播列表
        Route::post('advert','V1\AdvertController@addAdvert')->middleware('permission:advertAdd');//新增首页轮播
        Route::delete('advert','V1\AdvertController@delAdvert')->middleware('permission:advertDel');//删除首页轮播
        Route::post('store/category','V1\StoreController@addStoreCategory')->middleware('permission:storeCategoryAdd');//新增规格
        Route::get('store/categories','V1\StoreController@getStoreCategories')->middleware('permission:storeCategoryList');//获取规格列表
        Route::delete('store/category','V1\StoreController@deletesStoreCategory')->middleware('permission:storeCategoryDel');
        Route::get('settle/applies','V1\StoreController@getSettleApplies')->middleware('permission:settleList');
        Route::post('check/settle/apply','V1\StoreController@checkSettleApply')->middleware('permission:settleCheck');
        Route::post('product/type','V1\ProductController@createProductType')->middleware('permission:productTypeAdd');
        Route::delete('product/type','V1\ProductController@delProductType')->middleware('permission:productTypeDel');
        Route::get('documents','V1\SystemController@getDocuments')->middleware('permission:documentList');
        Route::post('document','V1\SystemController@createDocument')->middleware('permission:documentAdd');
        Route::delete('document','V1\SystemController@delDocument')->middleware('permission:documentDel');
        Route::post('product/category','V1\ProductController@addProductCategory')->middleware('permission:productCategoryAdd');
        Route::get('product/categories','V1\ProductController@getProductCategories')->middleware('permission:productCategoryList');
        Route::post('edit/product/category','V1\ProductController@editProductCategory')->middleware('permission:productCategoryList');
        Route::delete('product/category','V1\ProductController@delProductCategory')->middleware('permission:productCategoryDel');
        Route::post('store','V1\StoreController@addStore')->middleware('permission:myStore');
        Route::get('stores','V1\StoreController@getStores')->middleware('permission:storeList');
        Route::post('express','V1\StoreController@addExpress')->middleware('permission:expressAdd');
        Route::get('expresses','V1\StoreController@getStoreExpresses')->middleware('permission:expressList');
        Route::delete('express','V1\StoreController@delExpress')->middleware('permission:expressDel');
        Route::post('product','V1\ProductController@addProduct')->middleware('permission:productAdd');
        Route::get('product','V1\ProductController@getProduct')->middleware('permission:productListAll|productListStore');
        Route::get('del/product','V1\ProductController@softDelProduct')->middleware('permission:productSoftDel');
        Route::delete('product','V1\ProductController@delProduct')->middleware('permission:productDel');
        Route::get('check/product','V1\ProductController@checkProduct')->middleware('permission:productReview');
        Route::get('shelf/product','V1\ProductController@shelfProduct')->middleware('permission:productShelf');
        Route::get('products','V1\ProductController@getProducts')->middleware(['checkStore','permission:productListAll|productListStore']);
        Route::post('product/notify','V1\ProductController@addNotifyQueue')->middleware('permission:productListAll');
        Route::get('type/products','V1\ProductController@getProductsByType')->middleware(['checkStore','permission:productListAll|productListStore']);
        Route::post('role','V1\SystemController@addRole')->middleware('permission:roleAdd');
        Route::get('roles','V1\SystemController@getRoles')->middleware('permission:roleList');
        Route::get('default/role','V1\RoleController@addDefaultRole')->middleware('permission:roleList');
        Route::delete('role','V1\SystemController@delRole')->middleware('permission:roleDel');
        Route::post('user','V1\UserController@addUser')->middleware('permission:adminAdd');
        Route::get('users','V1\UserController@getUsers')->middleware('permission:adminList');
        Route::get('withdraw/applies','V1\UserController@getWithdrawApplies')->middleware('permission:withdrawList');
        Route::get('withdraw/pass','V1\UserController@passWithdrawApply')->middleware('permission:withdrawCheck');
        Route::get('withdraw/reject','V1\UserController@rejectWithdrawApply')->middleware('permission:withdrawCheck');
        Route::post('express/config','V1\StoreController@addExpressConfig')->middleware('permission:expressConfig');
        Route::get('express/config','V1\StoreController@getExpressConfig')->middleware('permission:expressConfig');
        Route::get('wechat/users','V1\WeChatController@getWechatUsers')->middleware('permission:userList');
        Route::post('tx/config','V1\SystemController@addTxConfig')->middleware('permission:txConfig');
        Route::get('tx/config','V1\SystemController@getTxConfig')->middleware('permission:txConfig');
        Route::get('brokerages','V1\UserController@getBrokerageList')->middleware('permission:brokerageList');
        Route::get('delete/advert','V1\AdvertController@delAdvert')->middleware('permission:advertDel');
        Route::post('permission','V1\SystemController@addPermission');
        Route::get('permissions','V1\SystemController@getPermissions');
        Route::get('product/types','V1\ProductController@getProductTypes')->middleware('permission:productTypeList');
        Route::get('hot','V1\ProductController@addHot')->middleware('permission:productListAll');
        Route::get('new','V1\ProductController@addNew')->middleware('permission:productListAll');
        Route::get('offer','V1\ProductController@addOffer')->middleware('permission:productListAll');
        Route::get('hot/type','V1\ProductController@addHotType')->middleware('permission:productTypeList');
        Route::get('orders','V1\OrderController@getOrders')->middleware('permission:orderListStore|orderListAll');
        Route::get('order','V1\OrderController@getOrder')->middleware('permission:orderListStore|orderListAll');
        Route::post('ship/order','V1\OrderController@shipOrder')->middleware('permission:orderListDo');
        Route::get('proxy/applies','V1\UserController@listProxyApply')->middleware('permission:proxyApplyList');
        Route::get('proxy/pass','V1\UserController@passProxyApply')->middleware('permission:proxyApplyCheck');
        Route::get('proxy/reject','V1\UserController@rejectProxyApply')->middleware('permission:proxyApplyCheck');
        Route::get('proxy/users','V1\UserController@getProxyList')->middleware('permission:proxyList');
        Route::get('refuses','V1\OrderController@getRefuseList')->middleware('permission:refuseListStore|refuseListAll');
        Route::get('refuse','V1\OrderController@refuseOrder')->middleware('permission:refuseDo');
        Route::post('brokerage/ratio','V1\SystemController@addBrokerageRatio')->middleware('permission:brokerageList');
        Route::get('brokerage/ratio','V1\SystemController@getBrokerageRatio')->middleware('permission:brokerageList');
        Route::get('count','V1\SystemController@getCount');
        Route::get('newest/order','V1\OrderController@getNewestOrder');
        Route::post('notify/config','V1\SystemController@addNotifyConfig');
        Route::get('notify/configs','V1\SystemController@getNotifyConfigs');
        Route::post('poster/configs','V1\SystemController@addPosterConfigs');
        Route::get('poster/configs','V1\SystemController@getPosterConfigs');
        Route::post('icon/config','V1\SystemController@addIconConfig');
        Route::get('icon/configs','V1\SystemController@getIconConfigs');
    });
});
Route::group(['prefix'=>'v2','middleware'=>'cross'],function (){
    Route::post('card/promotion','V2\CardController@addCardPromotion');
    Route::get('card/promotions','V2\CardController@getCardPromotions');
    Route::get('card/promotion','V2\CardController@getCardPromotion');
    Route::get('hot/card/promotion','V2\CardController@addHotCardPromotion');
    Route::put('card/promotion','V2\CardController@modifyCardPromotion');
    Route::delete('card/promotion','V2\CardController@delCardPromotion');
    Route::get('check/promotion','V2\CardController@checkPromotion');
    Route::get('enable/promotion','V2\CardController@enablePromotion');
    Route::post('default/card','V2\CardController@addDefaultCard');
    Route::get('default/cards','V2\CardController@getDefaultCards');
    Route::get('product/stocks','V2\ProductController@getStockByProduct');
    Route::post('bargain/promotion','V2\BargainController@createBargain');
    Route::put('bargain/promotion','V2\BargainController@modifyBargainPromotion');
    Route::get('bargain/promotion','V2\BargainController@getBargainPromotion');
    Route::delete('bargain/promotion','V2\BargainController@delBargainPromotion');
    Route::get('bargain/promotions','V2\BargainController@getBargainPromotions');
    Route::get('check/bargain/promotion','V2\BargainController@checkPromotion');
    Route::get('enable/bargain/promotion','V2\BargainController@enablePromotion');
    Route::get('hot/bargain/promotion','V2\BargainController@addHotPromotion');
    Route::get('member/levels','V2\MemberController@getMemberLevels');
    Route::post('member/level','V2\MemberController@addMemberLevel');
    Route::delete('member/level','V2\MemberController@delMemberLevel');
    Route::post('member/user','V2\MemberController@addMemberUser');
    Route::get('member/users','V2\MemberController@getMemberUsers');
    Route::get('member/records','V2\MemberController@getMemberRecords');
});
Route::group(['prefix'=>'v3','middleware'=>'cross'],function (){
    Route::post('group/buy/promotion','V3\GroupBuyController@addGroupBuyPromotion');
    Route::get('group/buy/promotion','V3\GroupBuyController@getGroupBuyPromotion');
    Route::delete('group/buy/promotion','V3\GroupBuyController@delGroupBuyPromotion');
    Route::put('group/buy/promotion','V3\GroupBuyController@modifyGroupBuyPromotion');
    Route::get('group/buy/promotions','V3\GroupBuyController@getGroupBuyPromotions');
    Route::get('check/group/buy/promotion','V3\GroupBuyController@checkPromotion');
    Route::get('enable/group/buy/promotion','V3\GroupBuyController@enablePromotion');
    Route::get('hot/group/buy/promotion','V3\GroupBuyController@addHotPromotion');
    Route::post('sign/configs','V3\SignController@setSignConfigs');
    Route::get('sign/configs','V3\SignController@getSignConfigs');
    Route::post('coupon','V3\CouponController@addCoupon');
    Route::get('coupons','V3\CouponController@getCoupons');
    Route::delete('coupon','V3\CouponController@delCoupon');
    Route::get('enable/coupon','V3\CouponController@enableCoupon');
    Route::post('score/product','V3\ScoreController@addScoreProduct');
    Route::get('score/products','V3\ScoreController@getScoreProducts');
    Route::get('review/score/product','V3\ScoreController@reviewScoreProduct');
    Route::get('hot/score/product','V3\ScoreController@hotScoreProduct');
    Route::get('enable/score/product','V3\ScoreController@enableScoreProduct');
    Route::delete('score/product','V3\ScoreController@delScoreProduct');
    Route::get('score/product','V3\ScoreController@getScoreProduct');
    Route::post('score/config','V3\ScoreController@setScoreConfig');
    Route::get('score/config','V3\ScoreController@getScoreConfig');
    Route::post('store/express','V3\StoreController@setStoreExpress');
    Route::get('store/express','V3\StoreController@getStoreExpress');
    Route::post('discount/config','V3\DiscountController@addDiscountConfig');
    Route::get('discount/config','V3\DiscountController@getDiscountConfig');
    Route::post('store/withdraw','V3\StoreController@addWithdraw');
    Route::get('store/withdraws','V3\StoreController@getWithdraws')->middleware('permission:StoreWithdrawStore|StoreWithdrawAll');;
    Route::post('check/withdraw','V3\StoreController@checkWithdraw');
    Route::post('pickup/config','V3\SystemController@addPickUpConfig');
    Route::get('pickup/config','V3\SystemController@getPickUpConfig');
    Route::post('notify/promotion','V3\SystemController@notifyPromotion');
    Route::get('export/orders','V3\ExcelController@exportOrder');
    Route::post('prize/config','V3\PrizeController@addPrizeConfig');
    Route::get('prize/config','V3\PrizeController@getPrizeConfig');
    Route::get('prizes','V3\PrizeController@getPrizes');
    Route::post('prize','V3\PrizeController@addPrize');
    Route::delete('prize','V3\PrizeController@delPrize');
});
