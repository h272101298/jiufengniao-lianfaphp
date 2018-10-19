<?php

namespace App\Http\Controllers\V1;

use App\Libraries\WxPay;
use App\Libraries\Wxxcx;
use App\Modules\Address\Model\Address;
use App\Modules\Order\Model\Order;
use App\Modules\Product\Model\ProductDetailSnapshot;
use App\Modules\Product\Model\Stock;
use App\Modules\Score\Model\ScoreConfig;
use App\Modules\User;
use App\Modules\WeChatUser\Model\WeChatUser;
use function GuzzleHttp\Psr7\uri_for;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class OrderController extends Controller
{
    //
    private $handle;

    public function __construct()
    {
        $this->handle = new User();
    }

    public function createOrder(Request $post)
    {
        $user_id = getRedisData($post->token);
        $address = Address::find($post->address);
        $card_promotion = $post->card_promotion ? $post->card_promotion : 0;
        $bargain_promotion = $post->bargain_promotion ? $post->bargain_promotion : 0;
        $list_id = $post->list_id ? $post->list_id : 0;
        $express = $post->express ? $post->express : 0;
        $groupNumber = self::makePaySn($user_id);
        DB::beginTransaction();
        try {

            if ($card_promotion) {
                if ($this->handle->countCardPrize($user_id,$card_promotion)!=0){
                    throw new \Exception('活动已兑换！');
                }
                if ($this->handle->getUserCardCount($user_id,$card_promotion)!=5){
                    throw new \Exception('未集齐卡牌不能兑换！');
                }
                $promotion = $this->handle->getCardPromotion($card_promotion);
                $expressPrice = 0 ;
                if ($express){
                    $storeExpress = $this->handle->getStoreExpress($promotion->store_id);
                    $expressPrice = $storeExpress->price;
                }
                $stock = $this->handle->getStockById($promotion->stock_id);
                $price = sprintf('%.2f', $stock->price * ($promotion->offer / 10))+$expressPrice;
                $product = $this->handle->getProductById($stock->product_id);
                $state = $price==0?'paid':'created';
                $data = [
                    'user_id' => $user_id,
                    'number' => self::makePaySn($user_id),
                    'price' => $price,
                    'state' => $state,
                    'group_number' => $groupNumber,
                    'store_id' => $product->store_id,
                    'delivery'=>$express
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
                        'type'=>'card',
                        'promotion_id'=>$card_promotion
                    ];
                    $this->handle->addOrderType(0,$orderType);
                    if ($this->handle->addAddressSnapshot($order_id, $addressSnapshot)) {
                        $swapStock = $this->handle->getStockById($stock->id);
                        $product = $this->handle->getProductById($swapStock->product_id);
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
                            'price' => $price,
                            'number' => 1,
                            'product'=>$product->name
                        ];
                        $this->handle->addStockSnapshot($order_id, $stockData);
                        $this->handle->addCardPrize($user_id,$card_promotion);
                    }
                }
                DB::commit();
                return jsonResponse([
                    'msg' => 'ok',
                    'data' => [
                        'order' => $groupNumber,
                        'price'=>$price
                    ]
                ]);
            }
            if ($bargain_promotion){
                $bargainRecord = $this->handle->getOrderTypesIdByBargain($bargain_promotion);
                $bargainCount = empty($bargainRecord)?0:$this->handle->countOrder($bargainRecord,$user_id);
                if ($bargainCount){
                    return jsonResponse([
                        'msg'=>'已领取过该活动,请到我的订单页面查看！'
                    ],400);
                }
                $promotion = $this->handle->getBargainPromotion($bargain_promotion);
                $expressPrice = 0 ;
                if ($express){
                    $storeExpress = $this->handle->getStoreExpress($promotion->store_id);
                    $expressPrice = $storeExpress->price;
                }
                $list = $this->handle->getBargainListById($list_id);
                if ($promotion->number==0){
                    throw new \Exception('已无库存');
                }
                $stock = $this->handle->getStockById($list->stock_id);
                $product = $this->handle->getProductById($stock->product_id);
                $bargainStock = $this->handle->getBargainStock($bargain_promotion,$list->stock_id);
                $price = $bargainStock->origin_price - $this->handle->getBargainPromotionPrice($list_id) + $expressPrice;
                $state = $price==0?'paid':'created';
//                if ($price<$promotion->min_price){
//                    throw new \Exception('非法价格！');
//                }
                $data = [
                    'user_id' => $user_id,
                    'number' => self::makePaySn($user_id),
                    'price' => $price,
                    'state' => $state,
                    'group_number' => $groupNumber,
                    'store_id' => $product->store_id,
                    'delivery'=>$express
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
                        'type'=>'bargain',
                        'promotion_id'=>$bargain_promotion
                    ];
                    $this->handle->addOrderType(0,$orderType);
                    if ($this->handle->addAddressSnapshot($order_id, $addressSnapshot)) {
                        $swapStock = $this->handle->getStockById($stock->id);
                        $product = $this->handle->getProductById($swapStock->product_id);
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
                            'price' => $price,
                            'number' => 1,
                            'product'=>$product->name
                        ];
                        $this->handle->addStockSnapshot($order_id, $stockData);
                        $this->handle->addCardPrize($user_id,$card_promotion);
                    }
                }
                DB::commit();
                return jsonResponse([
                    'msg' => 'ok',
                    'data' => [
                        'order' => $groupNumber,
                        'price'=>$price
                    ]
                ]);
            }
            $stocks = $post->stocks;
            $stocksId = array_column($stocks, 'id');
            $storesId = $this->handle->getStoresIdByStockId($stocksId);
            $amount = 0;
            foreach ($storesId as $item) {
                $price = 0;
                $data = [
                    'user_id' => $user_id,
                    'number' => self::makePaySn($user_id),
                    'price' => 0,
                    'state' => 'created',
                    'group_number' => $groupNumber,
                    'store_id' => $item,
                    'delivery'=>$express
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
                        foreach ($stocks as $stock) {
                            $swapStock = $this->handle->getStockById($stock['id']);

                            $product = $this->handle->getProductById($swapStock->product_id);
                            if ($product->store_id == $item) {
                               // var_dump($swapStock->price);
                                //var_dump($stock['number']);
                                $price += $swapStock->price * $stock['number'];
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
                        }
                        $price = $price;
                        $amount += $price*$discount;
                        //var_dump($amount);
                        $orderPrice = [
                            'price' => $price
                        ];
                        $this->handle->addOrder($order_id, $orderPrice);
                    }
                }
            }
            if (number_format($amount,2)!=number_format($post->price,2)){
                throw new \Exception('非法价格！'.$amount);
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
            dd($exception);
            DB::rollBack();
            return jsonResponse(['msg' => '参数错误！'], 400);
        }


    }

    public function payOrder(Request $post)
    {
        $url = $post->getScheme() . '://' . $post->getHttpHost() . '/api/pay/notify';
        $user_id = getRedisData($post->token);
        $order_id = $post->order_id;
        $repay = $post->repay?$post->repay:0;
        $user = WeChatUser::findOrFail($user_id);
//        dd($user);
        if ($repay){
            $order = Order::where('number', '=', $order_id)->first();
            if ($order->state!='created'){
                return jsonResponse([
                    'msg' => '订单已支付！'
                ], 400);
            }
            //$price = Order::where('group_number', '=', $order_id)->sum('price');
            $wxPay = getWxPay($user->open_id);
            $data = $wxPay->pay($order_id, '购买商品', intval($order->price* 100) , $url,$post->getClientIp());
            $notify_id = $wxPay->getPrepayId();
            Order::where('number', '=', $order_id)->update(['notify_id' => $notify_id]);
            return response()->json([
                'msg' => 'ok',
                'data' => $data
            ]);
        }
        $count = Order::where('group_number', '=', $order_id)->where('state', '!=', 'created')->count();
        if ($count != 0) {
            return jsonResponse([
                'msg' => '订单已支付！'
            ], 400);
        }
        $order_user = Order::where('group_number', '=', $order_id)->pluck('user_id')->first();
        if ($order_user != $user_id) {
            return jsonResponse([
                'msg' => '无权操作！'
            ], 403);
        }
        $price = Order::where('group_number', '=', $order_id)->sum('price');
        $wxPay = getWxPay($user->open_id);
        $data = $wxPay->pay($order_id, '购买商品', intval($price * 100) , $url,$post->getClientIp());
        $notify_id = $wxPay->getPrepayId();
        Order::where('group_number', '=', $order_id)->update(['notify_id' => $notify_id]);
        return response()->json([
            'msg' => 'ok',
            'data' => $data
        ]);
    }

    public function getMyOrders()
    {
        $user_id = getRedisData(Input::get('token'));
        $state = Input::get('state', '');
        $page = Input::get('page', 1);
        $limit = Input::get('limit', 10);
        $type = Input::get('type');
        if ($type){
            $order_id = $this->handle->getOrdersIdByType($type);
        }else{
            $order_id = null;
        }
        $data = $this->handle->getMyOrders($user_id, $page, $limit, $state,$order_id);
        return jsonResponse([
            'msg' => 'ok',
            'data' => $data
        ]);
    }

    public function getOrders()
    {
        $search = Input::get('search');
        $page = Input::get('page', 1);
        $limit = Input::get('limit', 10);
        $start = Input::get('start');
        $end = Input::get('end');
        $idArray = [];
        if (!empty($search)){
            $idArr = $this->handle->getOrderIdByExpressName($search);
            $idArray = $this->handle->getOrderIdByStoreName($search);
            $idArray = array_merge($idArr, $idArray);
        }
        $type = Input::get('type');
        if (!empty($type)){
            $ordersId = $this->handle->getOrdersIdByOrderType($type);
//            var_dump($ordersId);
            $idArray = $ordersId;
        }
//        var_dump($idArray);
        $state = Input::get('state');
        $data = $this->handle->getOrders($page, $limit, $start, $end, $search, $idArray,null,$state,getStoreId());
        $this->handle->formatOrders($data['data']);
        return jsonResponse([
            'msg' => 'ok',
            'data' => $data
        ]);
    }

    public function getOrder()
    {
        $id = Input::get('id');
        $order = $this->handle->getOrderById($id);
        $this->handle->formatOrder($order);
        return jsonResponse([
            'msg' => 'ok',
            'data' => $order
        ]);
    }

    public function getNewestOrder()
    {
        $data = $this->handle->getNewestOrders(getStoreId());
        $this->handle->formatOrders($data['data']);
        return jsonResponse([
            'msg' => 'ok',
            'data' => $data
        ]);
    }

    public function shipOrder(Request $post)
    {
        $id = $post->id;
        $order = $this->handle->getOrderById($id);
        if ($order->state != 'paid') {
            return jsonResponse([
                'msg' => '当前状态不允许发货！'
            ], 400);
        }
        if ($order->store_id != getStoreId()) {
            return jsonResponse([
                'msg' => '无权操作！'
            ], 400);
        }
        $data = [
            'state' => 'delivery',
            'express' => $post->express,
            'express_number' => $post->express_number
        ];
        if ($this->handle->addOrder($id, $data)) {
            return jsonResponse([
                'msg' => 'ok'
            ]);
        }
        return jsonResponse([
            'msg' => '操作失败！'
        ], 400);
    }

    public function getOrderExpress()
    {
        $id = Input::get('id');
        $data = $this->handle->getExpressInfo($id);
        if ($data) {
            return jsonResponse([
                'msg' => 'ok',
                'data' => $data
            ]);
        }
        return jsonResponse([
            'msg' => '暂无物流信息！'
        ], 400);
    }

    public function confirmOrder()
    {
        $id = Input::get('id');
        $user_id = getRedisData(Input::get('token'));
        $order = $this->handle->getOrderByNumber($id);
        if ($order->user_id != $user_id) {
            return jsonResponse([
                'msg' => '无权操作！'
            ], 401);
        }
        if ($order->state != 'delivery') {
            return jsonResponse([
                'msg' => '当前状态不允许确认收货！'
            ], 400);
        }
        $data = [
            'state' => 'finished'
        ];
        $config = $this->handle->getScoreConfig();
        if (!empty($config)&&$config->state!=0){
            $score = floor(($config->ratio/100)*$order->price);
            if ($score!=0){
                $data = [
                    'user_id'=>$user_id,
                    'type'=>'1',
                    'score'=>$score,
                    'remark'=>'订单获得'
                ];
                $this->handle->addScoreRecord(0,$data);
                $this->handle->addUserScore2($user_id,$score);
            }
        }
        if ($this->handle->addOrder($order->id, $data)) {
            return jsonResponse([
                'msg' => 'ok'
            ]);
        }
        return jsonResponse([
            'msg' => '操作失败！'
        ]);
    }

    public function assessOrder(Request $post)
    {
        $orderId = $post->order_id;
        if ($orderId) {
            $order = $this->handle->getOrderByNumber($orderId);
            if ($order->is_assess) {
                return jsonResponse([
                    'msg' => '已评价的订单不能在评价！'
                ]);
            }
            $data = [
                'score' => $post->express_score,
                'is_assess' => 1
            ];
            $this->handle->addOrder($order->id, $data);
        }
        $stockId = $post->stock_id;
        $stock = $this->handle->getStockSnapshot($stockId);
        if ($stock->is_assess) {
            return jsonResponse([
                'msg' => '已评价的商品不能在评价！'
            ]);
        }
        $data = [
            'score' => $post->stock_score,
            'assess' => $post->assess,
            'is_assess' => 1
        ];
        $this->handle->addStockSnapshot($stock->order_id, $data, $stock->id);
        $this->handle->closeOrder($stock->order_id);
        return jsonResponse([
            'msg' => 'ok'
        ]);
    }

    public function cancelOrder()
    {
        $id = Input::get('id');
        $order = $this->handle->getOrderByNumber($id);
        if (!in_array($order->state, ['created', 'paid'])) {
            return jsonResponse([
                'msg' => '当前状态不能取消！'
            ], 400);
        }
        if ($order->state == 'paid') {
            $data = [
                'store_id' => $order->store_id,
                'remark' => '支付状态退款！'
            ];
            $this->handle->addRefuse(0, $order->id, $data);
        }
        $data = [
            'state' => 'canceled'
        ];
        if ($this->handle->addOrder($order->id, $data)) {
            return jsonResponse([
                'msg' => 'ok'
            ]);
        };
        return jsonResponse([
            'msg' => '系统错误！'
        ], 400);

    }

    public function getRefuseList()
    {
        $page = Input::get('page', 1);
        $limit = Input::get('limit', 10);
        $data = $this->handle->getRefuses(getStoreId(), $page, $limit);
        return jsonResponse([
            'msg' => 'ok',
            'data' => $data
        ]);
    }

    public function refuseOrder()
    {
        $id = Input::get('id');
        if ($this->handle->refuse($id)) {
            return jsonResponse([
                'msg' => 'ok'
            ]);
        };
        return jsonResponse([
            'msg' => '退款失败,请稍后再试！'
        ], 400);
    }

    public function payNotify(Request $post)
    {
        $data = $post->getContent();
        $wx = WxPay::xmlToArray($data);
        $wspay = getWxPay($wx['openid']);
        $data = [
            'appid' => $wx['appid'],
            'cash_fee' => $wx['cash_fee'],
            'bank_type' => $wx['bank_type'],
            'fee_type' => $wx['fee_type'],
            'is_subscribe' => $wx['is_subscribe'],
            'mch_id' => $wx['mch_id'],
            'nonce_str' => $wx['nonce_str'],
            'openid' => $wx['openid'],
            'out_trade_no' => $wx['out_trade_no'],
            'result_code' => $wx['result_code'],
            'return_code' => $wx['return_code'],
            'time_end' => $wx['time_end'],
            'total_fee' => $wx['total_fee'],
            'trade_type' => $wx['trade_type'],
            'transaction_id' => $wx['transaction_id']
        ];
        $sign = $wspay->getSign($data);
        if ($sign == $wx['sign']) {
            $orders = Order::where(['group_number' => $wx['out_trade_no']])->get();
            if (count($orders)){
                foreach ($orders as $order) {
                    if ($order->state =='created'){
                        $type = $this->handle->getOrderTypeByOrderId($order->id);
                        if (!empty($type)){
                            if ($type->type=='bargain'){
                                $promotion = $this->handle->getBargainPromotion($type->promotion_id);
                                $this->handle->addBargainPromotion($promotion->id,['number'=>$promotion->number-1]);
                            }
                            if ($type->type=='groupCreate'){
                                $promotion = $this->handle->getGroupBuyPromotion($type->promotion_id);
                                $list = $this->handle->getGroupBuyListByOrderId($order->id);
                                $time = time();
                                $data = [
                                    'start'=>$time,
                                    'end'=>$time+$promotion->time*60*60,
                                    'state'=>1
                                ];
                                $this->handle->addGroupBuyList($list->id,$data);
                                $join = $this->handle->getGroupBuyJoinByOrderId($order->id);
                                $this->handle->addGroupBuyJoin($join->id,['state'=>1]);
                            }
                            if ($type->type=='groupJoin'){
                                $join = $this->handle->getGroupBuyJoinByOrderId($order->id);
                                $this->handle->addGroupBuyJoin($join->id,['state'=>1]);
                            }
                        }
                        $data = [
                            'state' => 'paid',
                            'transaction_id' => $wx['transaction_id']
                        ];
                        $this->handle->addBrokerageQueue($order->id);
                        $this->handle->addOrder($order->id, $data);
                        $free = $this->handle->getGroupFree($order->user_id);
                        $this->handle->addGroupFree($order->user_id,$free+1);
                        $this->handle->addStoreAmount($order->store_id,$order->price);
                    }
                }
            }else{
                $order = $this->handle->getOrderByNumber($wx['out_trade_no']);
                if (!empty($order)){
                    if ($order->state =='created'){
                        $type = $this->handle->getOrderTypeByOrderId($order->id);
                        if (!empty($type)){
                            if ($type->type=='bargain'){
                                $promotion = $this->handle->getBargainPromotion($type->promotion_id);
                                $this->handle->addBargainPromotion($promotion->id,['number'=>$promotion->number-1]);
                            }
                            if ($type->type=='groupCreate'){
                                $promotion = $this->handle->getGroupBuyPromotion($type->promotion_id);
                                $list = $this->handle->getGroupBuyListByOrderId($order->id);
                                $time = time();
                                $data = [
                                    'start'=>$time,
                                    'end'=>$time+$promotion->time*60*60,
                                    'state'=>1
                                ];
                                $this->handle->addGroupBuyList($list->id,$data);
                                $join = $this->handle->getGroupBuyJoinByOrderId($order->id);
                                $this->handle->addGroupBuyJoin($join->id,['state'=>1]);
                            }
                            if ($type->type=='groupJoin'){
                                $join = $this->handle->getGroupBuyJoinByOrderId($order->id);
                                $this->handle->addGroupBuyJoin($join->id,['state'=>1]);
                            }
                        }
                        $data = [
                            'state' => 'paid',
                            'transaction_id' => $wx['transaction_id']
                        ];
                        $this->handle->addBrokerageQueue($order->id);
                        $this->handle->addOrder($order->id, $data);
                        $free = $this->handle->getGroupFree($order->user_id);
                        $this->handle->addGroupFree($order->user_id,$free+1);
                        $this->handle->addStoreAmount($order->store_id,$order->price);
                    }
                }
            }
            return 'SUCCESS';
        }
        return 'ERROR';
    }

    public function countUserOrders()
    {
        $user_id = getRedisData(Input::get('token'));
        $data = [
            'created' => $this->handle->countUserOrders($user_id, 'created'),
            'paid' => $this->handle->countUserOrders($user_id, 'paid'),
            'delivery' => $this->handle->countUserOrders($user_id, 'delivery'),
            'finish' => $this->handle->countUserOrders($user_id, 'finished'),
            'closed' => $this->handle->countUserOrders($user_id, 'closed'),
            'canceled' => $this->handle->countUserOrders($user_id, 'canceled'),
        ];
        return jsonResponse([
            'msg' => 'ok',
            'data' => $data
        ]);
    }

}
