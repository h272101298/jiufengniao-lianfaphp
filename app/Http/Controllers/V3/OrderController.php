<?php

namespace App\Http\Controllers\V3;

use App\Modules\Address\Model\Address;
use App\Modules\Product\Model\ProductDetailSnapshot;
use App\Modules\Score\Model\ScoreProduct;
use App\Modules\Score\Model\ScoreProductStock;
use App\Modules\User;
use function GuzzleHttp\Psr7\uri_for;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    //
    private $handle;
    public function __construct()
    {
        $this->handle = new User();
    }
    public function makeOrder(Request $post)
    {
        $user_id = getRedisData($post->token);
        $address = Address::find($post->address);
        $group_id = $post->group_id ? $post->group_id : 0;
        $stock_id = $post->stock_id ? $post->stock_id : 0;
        $list_id = $post->list_id ? $post->list_id : 0;
        $coupon_id = $post->coupon_id ? $post->coupon_id : 0;
        $groupNumber = self::makePaySn($user_id);
        $number = $post->number ? $post->number :1;
        $express = $post->express?$post->express:0;
        $couponPrice = 0;
        DB::beginTransaction();
        try {
            if ($list_id){
                $list = $this->handle->getGroupBuyList($list_id);
                if ($list->end<time()){
                    throw new \Exception('当前团购已过期！');
                }
                $group_id = $list->group_id;
                $groupBuy = $this->handle->getGroupBuyPromotion($group_id);
                if ($groupBuy->state!=2||$groupBuy->enable!=1){
                    throw new \Exception('当前团购已过期！');
                }
                $groupStock = $this->handle->getGroupBuyStock($group_id,$stock_id);
                $stock = $this->handle->getStockById($stock_id);
                $product = $this->handle->getProductById($stock->product_id);
                //$store = $this->handle->getStoreById($product->store_id);
                $state = 'created';
                $price = $groupStock->group_price*$number;
//                if (isset($coupon_id)&&$coupon_id!=0){
//                    $coupon = $this->handle->getUserCoupon($coupon_id);
//                    if ($coupon->user_id!=$user_id||$coupon->store_id!=$product->store_id||$coupon->state!=1){
//                        throw new \Exception('优惠券不可用！');
//                    }
//                    $info = $this->handle->getCoupon($coupon->coupon_id);
//                    if ($info->limit_price>$price){
//                        throw new \Exception('优惠券不可用！');
//                    }
//                    $couponPrice = $info->price;
//                    $price -= $couponPrice;
//                }
                if ($express==1){
                    $express = $this->handle->getStoreExpress($groupBuy->store_id);
                    $price+=$express->price;
                    $delivery = 1;
                }
                $data = [
                    'user_id' => $user_id,
                    'number' => self::makePaySn($user_id),
                    'price' => $price,
                    'state' => $state,
                    'group_number' => $groupNumber,
                    'store_id' => $product->store_id,
                    'delivery'=>$delivery
                ];
                $order_id = $this->handle->addOrder(0, $data);
                if ($order_id){
                    $addressSnapshot = [
                        'name' => $address->name,
                        'phone' => $address->phone,
                        'address' => $address->city . $address->address,
                        'zip_code' => $address->zip_code
                    ];
                    $orderType = [
                        'order_id'=>$order_id,
                        'type'=>'groupJoin',
                        'promotion_id'=>$group_id
                    ];
                    $this->handle->addOrderType(0,$orderType);
                    if ($this->handle->addAddressSnapshot($order_id, $addressSnapshot)) {
                        if ($product->norm == 'fixed') {
                            $detail = 'fixed';
                        } else {
                            $detail = explode(',', $stock->product_detail);
                            $detail = ProductDetailSnapshot::whereIn('id', $detail)->pluck('title')->toArray();
                            $detail = implode(' ', $detail);
                        }
                        $stockData = [
                            'product_id' => $stock->product_id,
                            'store_id' => $product->store_id,
                            'cover' => $stock->cover,
                            'name' => $product->name,
                            'detail' => $detail,
                            'price' => $groupStock->group_price,
                            'number' => $number,
                            'product'=>$product->name
                        ];
                        $this->handle->addStockSnapshot($order_id, $stockData);
                        $joinData = [
                            'user_id'=>$user_id,
                            'list_id'=>$list_id,
                            'stock_id' => $stock->id,
                            'order_id'=>$order_id,
                            'group_id'=>$group_id,
                            'state'=>1
                        ];
                        $this->handle->addGroupBuyJoin(0,$joinData);
                    }
                }
                DB::commit();
                return jsonResponse([
                    'msg' => 'ok',
                    'data' => [
                        'order' => $groupNumber,
                        'price'=>$price,
                        'list_id'=>$list_id
                    ]
                ]);
            }

            $groupBuy = $this->handle->getGroupBuyPromotion($group_id);
            if ($groupBuy->state!=2||$groupBuy->enable!=1){
                throw new \Exception('当前团购已过期！');
            }
            $groupStock = $this->handle->getGroupBuyStock($group_id,$stock_id);
            $stock = $this->handle->getStockById($stock_id);
            $product = $this->handle->getProductById($stock->product_id);
            //$store = $this->handle->getStoreById($product->store_id);
            $state = $groupBuy->free == 1 ? 'paid':'created';
            $price = $groupBuy->free == 1 ?0:$groupStock->group_price*$number;
//            dd($groupBuy);
//            if (isset($coupon_id)&&$coupon_id!=0){
//                $coupon = $this->handle->getUserCoupon($coupon_id);
//                if ($coupon->user_id!=$user_id||$coupon->store_id!=$product->store_id||$coupon->state!=1){
//                    throw new \Exception('优惠券不可用！');
//                }
//                $info = $this->handle->getCoupon($coupon->coupon_id);
//                if ($info->limit_price>$price){
//                    throw new \Exception('优惠券不可用！'.$price);
//                }
//                $couponPrice = $info->price;
//                $price -= $couponPrice;
//            }
            if ($express==1){
                $express = $this->handle->getStoreExpress($groupBuy->store_id);
                $price+=$express->price;
                $delivery = 1;
            }
            $data = [
                'user_id' => $user_id,
                'number' => self::makePaySn($user_id),
                'price' => $price,
                'state' => $state,
                'group_number' => $groupNumber,
                'store_id' => $product->store_id,
                'delivery'=>$delivery
            ];
            $order_id = $this->handle->addOrder(0, $data);
            if ($order_id){
                $addressSnapshot = [
                    'name' => $address->name,
                    'phone' => $address->phone,
                    'address' => $address->city . $address->address,
                    'zip_code' => $address->zip_code
                ];
                $orderType = [
                    'order_id'=>$order_id,
                    'type'=>'groupCreate',
                    'promotion_id'=>$group_id
                ];
                $this->handle->addOrderType(0,$orderType);
                if ($this->handle->addAddressSnapshot($order_id, $addressSnapshot)) {
                    if ($product->norm == 'fixed') {
                        $detail = 'fixed';
                    } else {
                        $detail = explode(',', $stock->product_detail);
                        $detail = ProductDetailSnapshot::whereIn('id', $detail)->pluck('title')->toArray();
                        $detail = implode(' ', $detail);
                    }
                    $stockData = [
                        'product_id' => $stock->product_id,
                        'stock_id' => $stock->id,
                        'store_id' => $product->store_id,
                        'cover' => $stock->cover,
                        'name' => $product->name,
                        'detail' => $detail,
                        'price' => $groupStock->group_price,
                        'number' => $number,
                        'product'=>$product->name
                    ];
                    $this->handle->addStockSnapshot($order_id, $stockData);
                    $listData = [
                        'user_id'=>$user_id,
                        'group_id'=>$group_id,
                        'order_id'=>$order_id
                    ];
                    $list_id = $this->handle->addGroupBuyList(0,$listData);
                    $joinData = [
                        'user_id'=>$user_id,
                        'list_id'=>$list_id,
                        'group_id'=>$group_id,
                        'stock_id'=>$stock_id,
                        'order_id'=>$order_id
                    ];
                    $this->handle->addGroupBuyJoin(0,$joinData);
                }
            }
            DB::commit();
            return jsonResponse([
                'msg' => 'ok',
                'data' => [
                    'order' => $groupNumber,
                    'price'=>$price,
                    'list_id'=>$list_id
                ]
            ]);
        } catch (\Exception $exception) {
            dd($exception);
            DB::rollBack();
            return jsonResponse(['msg' => '参数错误！'], 400);
        }
    }
    public function createOrder(Request $post)
    {
        $user_id = getRedisData($post->token);
        $address = Address::find($post->address);
        $groupNumber = self::makePaySn($user_id);
        $stores = $post->stores;
        DB::beginTransaction();
        try {
            $amount = 0;
            $discount2 = 1;
            $config = $this->handle->getDiscountConfig();
            $items = $this->handle->getDisCountItems();
            $discountConfig = 0;
            if (!empty($config)&&$config->state==1){
                $discountConfig = $config->type;
                if ($discountConfig==1){
                    $discount2 = $config->ratio/10;
                }
            }
            foreach ($stores as $item) {
                if ($discountConfig==3){
                    if (in_array($item,$items)){
                        $discount2 = $config->ratio/10;
                    }
                }
                $price = 0;
                $stocks = $item['stocks'];
                $originPrice = 0;
                $couponPrice = 0;
                $expressPrice = 0;
                $delivery = 0;
                if ($item['express']==1){
                    $express = $this->handle->getStoreExpress($item['id']);
                    $expressPrice+=empty($express)?0:$express->price;
                    $delivery = 1;
                }
                $data = [
                    'user_id' => $user_id,
                    'number' => self::makePaySn($user_id),
                    'price' => 0,
                    'state' => 'created',
                    'group_number' => $groupNumber,
                    'store_id' => $item['id'],
                    'delivery'=>$delivery
                ];
                $order_id = $this->handle->addOrder(0, $data);

                if ($order_id) {
                    $addressSnapshot = [
                        'name' => $address->name,
                        'phone' => $address->phone,
                        'address' => $address->city . $address->address,
                        'zip_code' => $address->zip_code
                    ];
                    $orderType = [
                        'order_id'=>$order_id,
                        'type'=>'origin',
                        'promotion_id'=>0
                    ];
                    $this->handle->addOrderType(0,$orderType);
                    if ($this->handle->addAddressSnapshot($order_id, $addressSnapshot)) {
                        $member = $this->handle->getMemberUser($user_id);
                        if (empty($member)||$member->end<time()){
                            $discount = 1;
                        }else{
                            $discount = $member->discount/10;
                        }
//                        var_dump($stocks);
                        foreach ($stocks as $stock) {
//                            var_dump($stock);
                            $swapStock = $this->handle->getStockById($stock['id']);
                            $product = $this->handle->getProductById($swapStock->product_id);
                            if ($discountConfig==2){
                               if (in_array($product->type_id,$items)){
                                   $discount2 = $config->ratio/10;
                               }
                            }
                            if ($discountConfig==4){
                                if (in_array($product->id,$items)){
                                    $discount2 = $config->ratio/10;
                                }
                            }
//                            var_dump($swapStock);

//                            var_dump($product);
                            if ($product->store_id == $item['id']) {
//                                 var_dump($swapStock->price);
                                //var_dump($stock['number']);
                                $originPrice +=  $swapStock->price * $stock['number'];
                                $price += $swapStock->price * $stock['number'];
//                                var_dump($price);
//                                var_dump($swapStock->price);

                                if ($product->norm == 'fixed') {
                                    $detail = 'fixed';
                                } else {
                                    $detail = explode(',', $swapStock->product_detail);
                                    $detail = ProductDetailSnapshot::whereIn('id', $detail)->pluck('title')->toArray();
                                    $detail = implode(' ', $detail);
                                }
                                $stockData = [
                                    'product_id' => $swapStock->product_id,
                                    'stock_id' => $swapStock->id,
                                    'store_id' => $product->store_id,
                                    'cover' => $swapStock->cover,
                                    'name' => $product->name,
                                    'detail' => $detail,
                                    'price' => $swapStock->price*$discount,
                                    'number' => $stock['number'],
                                    'product'=>$product->name
                                ];
                                $this->handle->addStockSnapshot($order_id, $stockData);
                            }
                            $this->handle->addProduct($product->id,['sales_volume'=>$product->sales_volume+$stock['number']]);
                        }
                        if (isset($item['coupon_id'])&&$item['coupon_id']!=0){
                            $coupon = $this->handle->getUserCoupon($item['coupon_id']);
                            if ($coupon->user_id!=$user_id||$coupon->store_id!=$item['id']||$coupon->state!=1){
                                throw new \Exception('优惠券不可用！');
                            }
                            $info = $this->handle->getCoupon($coupon->coupon_id);
                            if ($info->limit_price>$originPrice){
                                throw new \Exception('优惠券不可用！');
                            }
//                            dd($info);
                            $couponPrice = $info->price;
                            //var_dump($couponPrice);
                        }
//                        var_dump($price);
                        $price = $price*$discount*$discount2;
//                        var_dump($price);
                        $amount += round($price-$couponPrice+$expressPrice,2);
//                        var_dump($amount);
                        $orderPrice = [
                            'price' => $amount
                        ];
                        $this->handle->addOrder($order_id, $orderPrice);
                    }
                }
            }
            if (number_format($amount,2)!=number_format($post->price,2)){
                throw new \Exception('非法价格！'.number_format($amount,2).'d'.$expressPrice);
            }
            DB::commit();
            return jsonResponse([
                'msg' => 'ok',
                'data' => [
                    'order' => $groupNumber,
                    'price'=>number_format($amount,2)
                ]
            ]);
        } catch (\Exception $exception) {
//            dd($exception);
            DB::rollBack();
            return jsonResponse(['msg' => '参数错误！'.$exception->getMessage(),
                'line'=>$exception->getTrace()], 400);
        }
    }
    public function createScoreOrder(Request $post)
    {
        $user_id = getRedisData($post->token);
        $address = Address::find($post->address);
        $groupNumber = self::makePaySn($user_id);
        DB::beginTransaction();
        $stock_id = $post->stock_id;
        $express = $post->express ? $post->express : 0;
        $number = $post->number ? $post->number : 1;
        try{
            $expressPrice = 0;
            $stock = ScoreProductStock::find($stock_id);
            $product = ScoreProduct::find($stock->product_id);
            if ($express){
                $storeExpress = $this->handle->getStoreExpress($product->store_id);
                $expressPrice = $storeExpress->price;
            }
            $score = $this->handle->getUserScore($user_id);
            if ($score<$stock->score){
                throw new \Exception('积分不足!');
            }
            $price = $stock->price + $expressPrice;
            $data = [
                'user_id' => $user_id,
                'number' => self::makePaySn($user_id),
                'price' => $price,
                'state' => $price==0?'paid':'created',
                'group_number' => $groupNumber,
                'store_id' => $product->store_id,
                'delivery'=>$express,
                'score'=>$stock->score*$number
            ];
            $order_id = $this->handle->addOrder(0, $data);
            if ($order_id) {
                $data = [
                    'user_id'=>$user_id,
                    'type'=>2,
                    'score'=>$stock->score*$number,
                    'remark'=>'购买商品'
                ];
                $this->handle->addScoreUseRecord(0,$data);
                $addressSnapshot = [
                    'name' => $address->name,
                    'phone' => $address->phone,
                    'address' => $address->city . $address->address,
                    'zip_code' => $address->zip_code
                ];
                $orderType = [
                    'order_id'=>$order_id,
                    'type'=>'scoreOrder',
                    'promotion_id'=>$stock->product_id
                ];
                $this->handle->addOrderType(0,$orderType);}
                if ($this->handle->addAddressSnapshot($order_id, $addressSnapshot)){
                    if ($product->norm == 'fixed') {
                        $detail = 'fixed';
                    } else {
                        $detail = explode(',', $stock->product_detail);
                        $detail = ProductDetailSnapshot::whereIn('id', $detail)->pluck('title')->toArray();
                        $detail = implode(' ', $detail);
                    }
                    $stockData = [
                        'product_id' => $stock->product_id,
                        'stock_id' => $stock->id,
                        'store_id' => $product->store_id,
                        'cover' => $stock->cover,
                        'name' => $product->name,
                        'detail' => $detail,
                        'price' => $stock->price,
                        'number' => $number,
                        'score'=>$stock->score,
                        'product'=>$product->name
                    ];
                    $recordData = [
                        'user_id'=>$user_id,
                        'product_id'=>$product->id,
                        'order_id'=>$order_id,
                        'score'=>$stock->score*$number
                    ];
                    $this->handle->addExchangeRecord(0,$recordData);
                    $this->handle->addStockSnapshot($order_id, $stockData);
                    $this->handle->addUserScore($user_id,$score-($stock->score*$number));
                }
            DB::commit();
            return jsonResponse([
                'msg' => 'ok',
                'data' => [
                    'order' => $groupNumber,
                    'price'=>$price
                ]
            ]);
        }catch (\Exception $exception){
            DB::rollback();
            return jsonResponse([
                'msg'=>$exception->getMessage()
            ],400);
        }
    }
}
