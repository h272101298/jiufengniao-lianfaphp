<?php
/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/7/24
 * Time: 上午11:12
 */

namespace App\Modules\Member;


use App\Modules\Member\Model\MemberLevel;
use App\Modules\Member\Model\MemberUser;
use App\Modules\WeChatUser\Model\UserInfo;
use App\Modules\WeChatUser\Model\WeChatUser;
use Illuminate\Support\Facades\DB;

trait MemberHandle
{
    public function getMemberLevels($name='',$page=0,$limit=10)
    {
        $db = DB::table('member_levels');
        if ($name){
            $db->where('name','like','%'.$name.'%');
        }
        $count = $db->count();
        $data = $db->orderBy('id','DESC')->limit($limit)->offset(($page-1)*$limit)->get();
        return [
            'count'=>$count,
            'data'=>$data
        ];
    }
    public function getMemberLevel($id)
    {
        return MemberLevel::findOrFail($id);
    }
    public function delMemberLevel($id)
    {
        $level = MemberLevel::findOrFail($id);
        if ($level->delete()){
            MemberUser::where('level_id','=',$id)->delete();
            return true;
        }
        return false;
    }
    public function countMemberUsers($level)
    {
        return MemberUser::where('level_id','=',$level)->where('end','>',time())->count();
    }
    public function addMemberLevel($id,$data)
    {
        if ($id){
            $level = MemberLevel::find($id);
        }else{
            $level = new MemberLevel();
        }
        foreach ($data as $key=>$value){
            $level->$key = $value;
        }
        if ($level->save()){
            return true;
        }
        return false;
    }
    public function addMemberUser($id,$data)
    {
        if ($id){
            $user = MemberUser::findOrFail($id);
        }else{
            $user = new MemberUser();
        }
        foreach ($data as $key=>$value){
            $user->$key = $value;
        }
        if ($user->save()){
            return true;
        }
        return false;
    }
    public function getMemberUser($user_id)
    {
        $user = MemberUser::where('user_id','=',$user_id)->first();
        return $user;
    }
    public function getMemberUsers($user_id=null,$level=0,$page=1,$limit=10)
    {
        $db = DB::table('member_users');
        if (!empty($user_id)){
            $db->whereIn('user_id',$user_id);
        }
        if ($level){
            $db->where('level_id','=',$level);
        }
        $count = $db->count();
        $data = $db->limit($limit)->offset(($page-1)*$limit)->get();
        return [
            'data'=>$data,
            'count'=>$count
        ];
    }
    public function formatMemberUsers(&$users)
    {
        if (empty($users)){
            return [];
        }
        foreach ($users as $user){
            $user->user = WeChatUser::find($user->user_id);
            $user->info = UserInfo::where('user_id','=',$user->user_id)->first();
            $user->level = MemberLevel::find($user->level_id);
            $user->end = date('Y-m-d H:i:s',$user->end);
        }
        return $users;
    }
}