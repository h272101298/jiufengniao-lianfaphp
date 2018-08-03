<?php

namespace App\Http\Controllers\V3;

use App\Modules\Address\Model\Address;
use App\Modules\Product\Model\ProductDetailSnapshot;
use App\Modules\User;
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
        $groupNumber = self::makePaySn($user_id);
        $number = $post->number ? $post->number :1;
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
                $data = [
                    'user_id' => $user_id,
                    'number' => self::makePaySn($user_id),
                    'price' => $price,
                    'state' => $state,
                    'group_number' => $groupNumber,
                    'store_id' => $product->store_id
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
                            'price' => $price,
                            'number' => $number
                        ];
                        $this->handle->addStockSnapshot($order_id, $stockData);
                        $joinData = [
                            'user_id'=>$user_id,
                            'list_id'=>$list_id,
                            'stock_id' => $stock->id,
                            'order_id'=>$order_id,
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
                        'price'=>$price
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
            $data = [
                'user_id' => $user_id,
                'number' => self::makePaySn($user_id),
                'price' => $price,
                'state' => $state,
                'group_number' => $groupNumber,
                'store_id' => $product->store_id
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
                        'price' => $price,
                        'number' => $number
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
                    'price'=>$price
                ]
            ]);

        } catch (\Exception $exception) {
            dd($exception);
            DB::rollBack();
            return jsonResponse(['msg' => '参数错误！'], 400);
        }
    }
}
