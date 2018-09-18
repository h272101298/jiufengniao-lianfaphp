<?php

namespace App\Http\Controllers\V3;

use App\Modules\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class CouponController extends Controller
{
    //
    private $handle;
    public function __construct()
    {
        $this->handle = new User();
    }

    public function addCoupon(Request $post)
    {
        $id = $post->id?$post->id:0;
        $data = [
            'store_id'=>getStoreId(),
            'end'=>strtotime($post->end),
            'name'=>$post->name,
            'limit_price'=>$post->limit_price,
            'price'=>$post->price
        ];
        $result = $this->handle->addCoupon($id,$data);
        if ($result){
            $this->handle->addCouponType($result,'storeCoupon');
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function delCoupon()
    {
        $id = Input::get('id');
//        if ($this->handle->checkUserCoupon(0,$id)){
//            return jsonResponse([
//                'msg'=>'已被领券的优惠券不能删除！'
//            ]);
//        };
        if ($this->handle->delCoupon($id)){
            $this->handle->delUserCoupon(0,$id);
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function getCoupons()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $data = $this->handle->getCoupons($page,$limit);
        $this->handle->formatCoupons($data['data']);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function enableCoupon()
    {
        $id = Input::get('id');
        $coupon = $this->handle->getCoupon($id);
        $data = [
            'enable'=>$coupon->enable==1?0:1
        ];
        if ($this->handle->addCoupon($id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        };
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function getStoreCoupons()
    {
        $product_id = Input::get('product_id');
        $product = $this->handle->getProductById($product_id);
        if (empty($product)){
            return jsonResponse([
                'msg'=>'没找到该商品！'
            ],404);
        }
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $coupons = $this->handle->getCoupons($page,$limit,$product->store_id,1);
        $this->handle->formatCoupons($coupons['data']);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$coupons
        ]);
    }
    public function addUserCoupon()
    {
        $user_id = getRedisData(Input::get('token'));
        $coupon_id = Input::get('coupon_id');
        if ($this->handle->checkUserCoupon($user_id,$coupon_id)){
            return jsonResponse([
                'msg'=>'不能重复领券！'
            ],400);
        }
        $coupon = $this->handle->getCoupon($coupon_id);
        if (empty($coupon)){
            return jsonResponse([
                'msg'=>'优惠券不存在！'
            ],404);
        }
        if ($this->handle->addUserCoupon($user_id,$coupon_id,$coupon->store_id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function myCoupons()
    {
        $page = Input::get('page');
        $limit = Input::get('limit');
        $state = Input::get('state',1);
        $product_id = Input::get('product_id');
        $store_id = Input::get('store_id',0);
        if ($product_id){
            $product = $this->handle->getProductById($product_id);
            if (empty($product)){
                return jsonResponse([
                    'msg'=>'没找到该商品！'
                ],404);
            }
            $store_id = $product->store_id;
        }
        $user_id = getRedisData(Input::get('token'));
        $coupons = $this->handle->getUserCoupons($user_id,$state,$page,$limit,$store_id);
        $this->handle->formatUserCoupons($coupons['data']);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$coupons
        ]);
    }
}
