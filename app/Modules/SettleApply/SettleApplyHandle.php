<?php
namespace App\Modules\SettleApply;
use App\Modules\SettleApply\Model\SettleApply;
use App\Modules\SettleApply\Model\UserSettleApply;
use App\User;
use Illuminate\Support\Facades\DB;
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
//        $apply = SettleApply::find($apply_id);
        return $apply_id;
    }
    public function countSettleApplyPhone($phone)
    {
        $idArr = SettleApply::where('phone','=',$phone)->pluck('id')->toArray();
        if (empty($idArr)){
            return 0;
        }
        return UserSettleApply::whereIn('apply_id',$idArr)->where('state','!=',3)->count();
    }
    public function getUserSettleApplyCount($token)
    {
        $uid = getRedisData($token);
        return UserSettleApply::where('user_id','=',$uid)->where('state','<',3)->count();
    }
    public function getSettleApplies($page,$limit,$state=0,$phone='',$name='')
    {
        $dbObj = DB::table('settle_applies');
        if ($state){
            $idArr = UserSettleApply::where('state','=',$state)->pluck('apply_id');
            $dbObj->whereIn('id',$idArr);
        }
        if ($phone){
            $dbObj->where('phone','like','%'.$phone.'%');
        }
        if ($name){
            $dbObj->where('name','like','%'.$phone.'%');
        }
        $count = $dbObj->count();
        $data = $dbObj->orderBy('id','DESC')->limit($limit)->offset(($page-1)*$limit)->get();
        return [
            'data'=>$this->formatSettleApplies($data),
            'count'=>$count
        ];
    }
    public function formatSettleApplies(&$applies)
    {
        if (count($applies)==0){
            return [];
        }
        $applyState = config('applyState');
        for ($i=0;$i<count($applies);$i++){
            $swap = UserSettleApply::where('apply_id','=',$applies[$i]->id)->first();
            $applies[$i]->check_id = $swap->id;
            $applies[$i]->state = $applyState[$swap->state];
        }
        return $applies;
    }
    public function checkSettleApply($id,$state)
    {
        $check = UserSettleApply::find($id);
        if ($state==1) {
            $check->state = 2;
        }else{
            $check->state = 3;
        }
        if ($check->save()){
            return $check->apply_id;
        }
        return false;
    }
    public function getSettleApplyById($id)
    {
        return SettleApply::findOrFail($id);
    }
//    public function
}