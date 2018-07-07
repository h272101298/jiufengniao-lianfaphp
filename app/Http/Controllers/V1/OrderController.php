<?php

namespace App\Http\Controllers\V1;

use App\Libraries\WxPay;
use App\Libraries\Wxxcx;
use App\Modules\Address\Model\Address;
use App\Modules\Order\Model\Order;
use App\Modules\Product\Model\ProductDetailSnapshot;
use App\Modules\Product\Model\Stock;
use App\Modules\User;
use App\Modules\WeChatUser\Model\WeChatUser;
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
        $stocks = $post->stocks;
        $stocksId = array_column($stocks,'id');
        $storesId = $this->handle->getStoresIdByStockId($stocksId);
        $groupNumber = self::makePaySn($user_id);
        DB::beginTransaction();
        try{
            $orderNumber =[];
            foreach ($storesId as $item) {
                $data = [
                    'user_id'=>$user_id,
                    'number'=>self::makePaySn($user_id),
                    'price'=>0,
                    'state'=>'created',
                    'group_number'=>$groupNumber,
                    'store_id'=>$item
                ];

                $order_id = $this->handle->addOrder(0,$data);
                if ($order_id){
                    $addressSnapshot = [
                        'name'=>$address->name,
                        'phone'=>$address->phone,
                        'address'=>$address->city.$address->address,
                        'zip_code'=>$address->zip_code
                    ];
                    if ($this->handle->addAddressSnapshot($order_id,$addressSnapshot)){
                        $price = 0;
                        foreach ($stocks as $stock){
                            $swapStock = $this->handle->getStockById($stock['id']);

                            $product = $this->handle->getProductById($swapStock->product_id);
                            if ($product->store_id == $item) {
                                $price+=$swapStock->price*$stock['number'];
                                if ($product->norm=='fixed'){
                                    $detail = 'fixed';
                                }else{
                                    $detail = explode(',',$swapStock->product_detail);
                                    $detail = ProductDetailSnapshot::whereIn('id',$detail)->pluck('title')->toArray();
                                    $detail = implode(' ',$detail);
                                }
                                $stockData = [
                                    'product_id'=>$swapStock->product_id,
                                    'stock_id'=>$swapStock->id,
                                    'store_id'=>$product->store_id,
                                    'cover'=>$swapStock->cover,
                                    'name'=>$product->name,
                                    'detail'=>$detail,
                                    'price'=>$swapStock->price,
                                    'number'=>$stock['number']
                                ];
                                $this->handle->addStockSnapshot($order_id,$stockData);
                            }

                        }
                        $order = $this->handle->getOrderById($order_id);
                        $orderPrice = [
                            'price'=>$price
                        ];
                        $this->handle->addOrder($order_id,$orderPrice);
                        array_push($orderNumber,$order->id);
                    }
                }
            }
            DB::commit();
            return jsonResponse([
                'msg'=>'ok',
                'data'=>[
                    'order'=>$groupNumber
                ]
            ]);
        }catch (\Exception $exception){
            dd($exception);
            DB::rollBack();
            return jsonResponse(['msg'=>'参数错误！'],400);
        }




    }
    public function payOrder(Request $post)
    {
        $url = 'https://template.geckowing.com/api/pay/notify';
        $user_id = getRedisData($post->token);
        $order_id = $post->order_id;
        $user = WeChatUser::findOrFail($user_id);
        $count = Order::where('group_number','=',$order_id)->where('state','!=','created')->count();
        if ($count!=0){
            return jsonResponse([
                'msg'=>'订单已支付！'
            ],400);
        }
        $order_user = Order::where('group_number','=',$order_id)->pluck('user_id')->first();
        if ($order_user!=$user_id){
            return jsonResponse([
                'msg'=>'无权操作！'
            ],403);
        }
        $price = Order::where('group_number','=',$order_id)->sum('price');
        $wxPay = getWxPay($user->open_id);
        $data = $wxPay->pay($order_id,'购买商品',($price)*100,$url);
        $notify_id = $wxPay->getPrepayId();
        Order::where('group_number','=',$order_id)->update(['notify_id'=>$notify_id]);
        return response()->json([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function getMyOrders()
    {
        $user_id = getRedisData(Input::get('token'));
        $state = Input::get('state','');
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $data = $this->handle->getMyOrders($user_id,$page,$limit,$state);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function getOrders()
    {
        $search = Input::get('search');
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $start = Input::get('start');
        $end = Input::get('end');
        $idArr = $this->handle->getOrderIdByExpressName($search);
        $idArray = $this->handle->getOrderIdByStoreName($search);
        $idArray = array_merge($idArr,$idArray);
        $data = $this->handle->getOrders($page,$limit,$start,$end,$search,$idArray);
        $this->handle->formatOrders($data['data']);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function getOrder()
    {
        $id = Input::get('id');
        $order = $this->handle->getOrderById($id);
        $this->handle->formatOrder($order);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$order
        ]);
    }
    public function getNewestOrder()
    {
        $data = $this->handle->getNewestOrders(getStoreId());
        $this->handle->formatOrders($data['data']);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function shipOrder(Request $post)
    {
        $id = $post->id;
        $order = $this->handle->getOrderById($id);
        if ($order->state!='paid'){
            return jsonResponse([
                'msg'=>'当前状态不允许发货！'
            ],400);
        }
        if ($order->store_id!=getStoreId()){
            return jsonResponse([
                'msg'=>'无权操作！'
            ],400);
        }
        $data = [
            'state'=>'delivery',
            'express'=>$post->express,
            'express_number'=>$post->express_number
        ];
        if ($this->handle->addOrder($id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'操作失败！'
        ],400);
    }
    public function getOrderExpress()
    {
        $id = Input::get('id');
        $data = $this->handle->getExpressInfo($id);
        if ($data){
            return jsonResponse([
                'msg'=>'ok',
                'data'=>$data
            ]);
        }
        return jsonResponse([
            'msg'=>'暂无物流信息！'
        ],400);
    }
    public function confirmOrder()
    {
        $id = Input::get('id');
        $user_id = getRedisData(Input::get('token'));
        $order = $this->handle->getOrderByNumber($id);
        if ($order->user_id!=$user_id){
            return jsonResponse([
                'msg'=>'无权操作！'
            ],401);
        }
        if ($order->state!='delivery'){
            return jsonResponse([
                'msg'=>'当前状态不允许确认收货！'
            ],400);
        }
        $data = [
            'state'=>'finished'
        ];
        if ($this->handle->addOrder($order->id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'操作失败！'
        ]);
    }
    public function assessOrder(Request $post)
    {
        $orderId = $post->order_id;
        if ($orderId){
            $order = $this->handle->getOrderByNumber($orderId);
            if ($order->is_assess){
                return jsonResponse([
                    'msg'=>'已评价的订单不能在评价！'
                ]);
            }
            $data = [
                'score'=>$post->express_score,
                'is_assess'=>1
            ];
            $this->handle->addOrder($order->id,$data);
        }
        $stockId = $post->stock_id;
        $stock = $this->handle->getStockSnapshot($stockId);
        if ($stock->is_assess){
            return jsonResponse([
                'msg'=>'已评价的商品不能在评价！'
            ]);
        }
        $data = [
            'score'=>$post->stock_score,
            'assess'=>$post->assess,
            'is_assess'=>1
        ];
        $this->handle->addStockSnapshot($stock->order_id,$data,$stock->id);
        $this->handle->closeOrder($stock->order_id);
        return jsonResponse([
            'msg'=>'ok'
        ]);
    }
    public function cancelOrder()
    {
        $id = Input::get('id');
        $order = $this->handle->getOrderByNumber($id);
        if (!in_array($order->state,['created','paid'])){
            return jsonResponse([
                'msg'=>'当前状态不能取消！'
            ],400);
        }
        if ($order->state=='paid'){
            $data = [
                'store_id'=>$order->store_id,
                'remark'=>'支付状态退款！'
            ];
            $this->handle->addRefuse(0,$order->id,$data);
        }
        $data = [
            'state'=>'canceled'
        ];
        if ($this->handle->addOrder($order->id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        };
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);

    }
    public function getRefuseList()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $data = $this->handle->getRefuses(getStoreId(),$page,$limit);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function refuseOrder()
    {
        $id = Input::get('id');
        if ($this->handle->refuse($id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        };
        return jsonResponse([
            'msg'=>'退款失败,请稍后再试！'
        ],400);
    }
    public function payNotify(Request $post)
    {
        $data = $post->getContent();
        $wx = WxPay::xmlToArray($data);
        $wspay =getWxPay($wx['openid']);
        $data = [
            'appid'=>$wx['appid'],
            'cash_fee'=>$wx['cash_fee'],
            'bank_type'=>$wx['bank_type'],
            'fee_type'=>$wx['fee_type'],
            'is_subscribe'=>$wx['is_subscribe'],
            'mch_id'=>$wx['mch_id'],
            'nonce_str'=>$wx['nonce_str'],
            'openid'=>$wx['openid'],
            'out_trade_no'=>$wx['out_trade_no'],
            'result_code'=>$wx['result_code'],
            'return_code'=>$wx['return_code'],
            'time_end'=>$wx['time_end'],
            'total_fee'=>$wx['total_fee'],
            'trade_type'=>$wx['trade_type'],
            'transaction_id'=>$wx['transaction_id']
        ];
        $sign = $wspay->getSign($data);
        if ($sign == $wx['sign']){
            $orders = Order::where(['group_number'=>$wx['out_trade_no']])->get();
            foreach ($orders as $order){
                $data = [
                    'state'=>'paid',
                    'transaction_id'=>$wx['transaction_id']
                ];
                $this->handle->addBrokerageQueue($order->id);
                $this->handle->addOrder($order->id,$data);
            }
            return 'SUCCESS';
        }
        return 'ERROR';
    }

}
