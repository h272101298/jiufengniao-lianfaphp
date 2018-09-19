<?php
/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/8/11
 * Time: 下午3:34
 */

namespace App\Modules\Coupon;


use App\Modules\Coupon\Model\Coupon;
use App\Modules\Coupon\Model\CouponRecord;
use App\Modules\Coupon\Model\CouponType;
use App\Modules\Coupon\Model\UserCoupon;
use App\Modules\Store\Model\Store;
use Illuminate\Support\Facades\DB;

trait CouponHandle
{
    public function addCoupon($id,$data)
    {
        if ($id){
            $coupon = Coupon::find($id);
        }else{
            $coupon = new Coupon();
        }
        foreach ($data as $key => $value){
            $coupon->$key = $value;
        }
        if ($coupon->save()){
            return $coupon->id;
        }
        return false;
    }
    public function getCoupons($page=1,$limit=10,$store_id=0,$enable=0)
    {
        $db = DB::table('coupons');
        if ($store_id){
            $db->where('store_id','=',$store_id);
        }
        if ($enable){
            $db->where('enable','=',$enable);
        }
        $count = $db->count();
        $data = $db->orderBy('id','DESC')->limit($limit)->offset(($page-1)*$limit)->get();
        return [
            'data'=>$data,
            'count'=>$count
        ];
    }
    public function formatCoupons(&$coupons,$store=1)
    {
        if (empty($coupons)){
            return true;
        }
        foreach ($coupons as $coupon){
            $coupon->end = date('Y-m-d H:i:s',$coupon->end);
            if ($store){
                $coupon->store = Store::find($coupon->store_id);
            }
        }
        return $coupons;
    }
    public function getCoupon($id)
    {
        return Coupon::findOrFail($id);
    }
    public function formatCoupon($coupon)
    {

    }
    public function delCoupon($id)
    {
        $coupon = Coupon::findOrFail($id);
        if ($coupon->delete()){
            CouponType::where('coupon_id','=',$id)->delete();
            return true;
        }
        return false;
    }
    public function addCouponType($coupon_id,$type)
    {
        $couponType = CouponType::where('coupon_id','=',$coupon_id)->first();
        if (empty($couponType)){
            $couponType = new CouponType();
            $couponType->coupon_id = $coupon_id;
        }
        $couponType->type = $type;
        if ($couponType->save()){
            return true;
        }
        return false;
    }
    public function getCouponType($coupon_id)
    {
        return CouponType::where('coupon_id','=',$coupon_id)->pluck('type')->first();
    }
    public function addUserCoupon($user_id,$coupon_id,$store_id)
    {
        $userCoupon = new UserCoupon();
        $userCoupon->user_id = $user_id;
        $userCoupon->coupon_id = $coupon_id;
        $userCoupon->store_id = $store_id;
        if ($userCoupon->save()){
            return $userCoupon->id;
        }
        return false;
    }
    public function modifyUserCoupon($id,$data)
    {
        $coupon = UserCoupon::findOrFail($id);
        foreach ($data as $key => $value){
            $coupon->$key = $value;
        }
        if ($coupon->save()){
            return true;
        }
        return false;
    }
    public function checkUserCoupon($user_id,$coupon_id)
    {
        $db = DB::table('user_coupons');
        if ($user_id){
            $db->where('user_id','=',$user_id);
        }
        if ($coupon_id){
            $db->where('coupon_id','=',$coupon_id);
        }
        return $db->count();
    }
    public function delUserCoupon($user_id=0,$coupon_id=0)
    {
        $db = DB::table('user_coupons');
        if ($user_id){
            $db->where('user_id','=',$user_id);
        }
        if ($coupon_id){
            $db->where('coupon_id','=',$coupon_id);
        }
        return $db->delete();
    }
    public function getUserCoupons($user_id,$state=0,$page=1,$limit=10,$store_id=0)
    {
        $db = UserCoupon::where('user_id','=',$user_id);
        if ($state){
            $db->where('state','=',$state);
        }
        if ($store_id){
            $db->where('store_id','=',$store_id);
        }
        $count = $db->count();
        $data = $db->orderBy('id','DESC')->limit($limit)->offset(($page-1)*$limit)->get();
        return [
            'data'=>$data,
            'count'=>$count
        ];
    }
    public function formatUserCoupons($coupons,$formatStore=1)
    {
        if (empty($coupons)){
            return [];
        }
        foreach ($coupons as $coupon){
            $info = Coupon::find($coupon->coupon_id);
            if (!empty($info)){
                $info->end = date('Y-m-d H:i:s',$info->end);
            }
            $coupon->info = $info;
            if ($formatStore){
                $coupon->store = Store::find($coupon->store_id);
            }
        }
        return $coupons;
    }
    public function getUserCoupon($id)
    {
        return UserCoupon::findOrFail($id);
    }
    public function addCouponRecord($coupon_id,$order_id)
    {
        $record = new CouponRecord();
        $record->order_id = $order_id;
        $record->coupon_id = $coupon_id;
        if ($record->save()){
            return true;
        }
        return false;
    }
    public function getCouponRecord($coupon_id)
    {

    }
}