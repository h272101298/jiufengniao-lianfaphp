<?php
namespace App\Modules\WeChatUser;
use App\Modules\Address\AddressHandle;
use App\Modules\Prize\PrizeHandle;
use App\Modules\Product\Model\Product;
use App\Modules\Proxy\Model\ProxyList;
use App\Modules\Proxy\Model\ProxyUser;
use App\Modules\Proxy\ProxyHandle;
use App\Modules\SettleApply\SettleApplyHandle;
use App\Modules\WeChatUser\Model\NotifyList;
use App\Modules\WeChatUser\Model\ProductCollect;
use App\Modules\WeChatUser\Model\UserAmount;
use App\Modules\WeChatUser\Model\UserInfo;
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
    use ProxyHandle;
    use PrizeHandle;
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
    public function listUsers($name='',$page,$limit)
    {
        $DbObj = DB::table('we_chat_users');
        if ($name){
            $DbObj->where('nickname','like','%'.$name.'%');
        }
        $count = $DbObj->count();
        $data = $DbObj->limit($limit)->offset(($page-1)*$limit)->get();
        return [
            'data'=>$data,
            'count'=>$count
        ];
    }
    public function formatUsers(&$users)
    {
        if (empty($users)){
            return [];
        }
        foreach ($users as $user){
            $user->info = UserInfo::where('user_id','=',$user->id)->first();
        }
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
    public function getUserAmount($user_id)
    {
        $amount = UserAmount::where('user_id','=',$user_id)->first();
        if (empty($amount)){
            $amount = new UserAmount();
            $amount->user_id = $user_id;
            $amount->amount = 0;
            $amount->save();
        }
        return $amount->amount;
    }
    public function addUserAmount($user_id,$price)
    {
        $amount = UserAmount::where('user_id','=',$user_id)->first();
        if (empty($amount)){
            $amount = new UserAmount();
            $amount->user_id = $user_id;
            $amount->amount = 0;
        }
        $amount->amount += $price;
        if ($amount->save()){
            return true;
        }
        return false;
    }
    public function addUserInfo($id=0,$data)
    {
        if ($id){
            $info = UserInfo::find($id);
        }else{
            $info = new UserInfo();
        }
        foreach ($data as $key=>$value){
            $info->$key = $value;
        }
        if ($info->save()){
            return true;
        }
        return false;
    }

    public function getUserInfoByUserId($user_id)
    {
        $info = UserInfo::where('user_id','=',$user_id)->first();
        return $info;
    }

    public function addProxyList($user_id,$proxy_id)
    {
        $count = ProxyUser::where('user_id','=',$proxy_id)->count();
        if ($count==0){
            return false;
        }
        $list = ProxyList::where('user_id','=',$user_id)->where('proxy_id','=',$proxy_id)->first();
        if (empty($list)){
            $list = new ProxyList();
            $list->user_id = $user_id;
            $list->proxy_id = $proxy_id;
            $list->save();
        }
        return true;
    }
    public function getUserProxyList($user_id,$page,$limit)
    {
        $count = ProxyList::where('proxy_id','=',$user_id)->count();
        $lists = ProxyList::where('proxy_id','=',$user_id)->limit($limit)->offset(($page-1)*$limit)->get();
        if (!empty($lists)){
            foreach ($lists as $list){
                $list->user = WeChatUser::find($list->user_id);
            }
        }
        return [
            'data'=>$lists,
            'count'=>$count
        ];
    }
    public function addNotifyList($user_id,$notify_id)
    {
        $user = WeChatUser::find($user_id);
        $list = new NotifyList();
        $list->open_id = $user->open_id;
        $list->notify_id = $notify_id;
        if ($list->save()){
            return true;
        }
        return false;
    }
    public function delNotifyList($id)
    {
        $list = NotifyList::findOrFail($id);
        if ($list->delete()){
            return true;
        }
        return false;
    }
    public function countWeChatUsers($created)
    {
        return WeChatUser::whereDate('created_at',$created)->count();
    }

}