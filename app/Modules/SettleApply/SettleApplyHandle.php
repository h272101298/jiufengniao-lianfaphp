<?php
namespace App\Modules\SettleApply;
use App\Modules\SettleApply\Model\SettleApply;
use App\Modules\SettleApply\Model\UserSettleApply;
use Illuminate\Support\Facades\Input;

/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/6/12
 * Time: 上午10:14
 */
trait SettleApplyHandle
{
    public function createSettleApply($token,$data)
    {
        $uid = getRedisData($token);
        $apply = new SettleApply();
        foreach ($data as $key=>$value){
            $apply->$key = $value;
        }
        if ($apply->save()){
            $userApply = new UserSettleApply();
            $userApply->user_id = $uid;
            $userApply->apply_id = $apply->id;
            $userApply->save();
            return true;
        }
        return false;
//        $apply->
    }
    public function getUserSettleApply($token)
    {
        $uid = getRedisData($token);
        $apply_id = UserSettleApply::where('user_id','=',$uid)->orderBy('id','DESC')->first();
        $apply = SettleApply::find($apply_id);
        return $apply;
    }
    public function getUserSettleApplyCount($token)
    {
        $uid = getRedisData($token);
        return UserSettleApply::where('user_id','=',$uid)->where('state','<=',3)->count();
    }
//    public function
}