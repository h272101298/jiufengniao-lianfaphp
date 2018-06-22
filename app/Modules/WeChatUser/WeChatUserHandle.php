<?php
namespace App\Modules\WeChatUser;
use App\Modules\Address\AddressHandle;
use App\Modules\SettleApply\SettleApplyHandle;
use App\Modules\WeChatUser\Model\WeChatUser;
use App\User;
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/6/6
 * Time: 下午5:25
 * 微信用户数据操作类
 */
class WeChatUserHandle {
    use AddressHandle;
    use SettleApplyHandle;
    //创建用户
    public function createUser($data)
    {

        $user = new WeChatUser();
//        dd($user);
        foreach ($data as $key=>$value){
            $user->$key = $value;
        }
        if ($user->save()){
            return $user->id;
        }
        return false;
//        dd($user);
    }
    //编辑用户
    public function editUser($token,$attrs)
    {
        $user = WeChatUser::findOrFail(getRedisData($token));
        foreach ($attrs as $attr=>$detail){
            $user->$attr = $detail;
        }
        if ($user->save()){
            return true;
        }
        return false;
    }

    //用户列表
    public function listUsers($page,$limit)
    {
        $DbObj = DB::table('we_chat_users');
        $DbObj->limit($limit)->offset(($page-1)*$limit)->get();
    }
    //删除用户
    public function delUser($id)
    {
        $user = WeChatUser::findOrFail($id);
        if ($user->delete()){
            return true;
        }
        return false;
    }
    //使用token获取用户
    public function getUserByToken($token)
    {
        $user_id = getRedisData($token);
        $user = WeChatUser::find($user_id);
        return $user;
    }
    public function findUserByOpenId($openId)
    {
        $user = WeChatUser::where('open_id','=',$openId)->first();
        return $user;
    }
    //
//    public function createSettleApply($token, $data)
//    {
//        $uid = getRedisData($token);
//
//    }

}