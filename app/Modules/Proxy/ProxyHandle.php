<?php
/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/7/4
 * Time: 下午5:38
 */

namespace App\Modules\Proxy;


use App\Modules\Order\Model\Order;
use App\Modules\Proxy\Model\Brokerage;
use App\Modules\Proxy\Model\BrokerageQueue;
use App\Modules\Proxy\Model\BrokerageRatio;
use App\Modules\Proxy\Model\ProxyApply;
use App\Modules\Proxy\Model\ProxyUser;
use App\Modules\Proxy\Model\WithdrawApply;
use App\Modules\WeChatUser\Model\UserAmount;
use App\Modules\WeChatUser\Model\WeChatUser;
use Illuminate\Support\Facades\DB;

trait ProxyHandle
{
    public function addProxyApply($id, $data)
    {
        if ($id) {
            $apply = ProxyApply::find($id);
        } else {
            $apply = new ProxyApply();
        }
        foreach ($data as $key => $value) {
            $apply->$key = $value;
        }
        if ($apply->save()) {
            return true;
        }
        return false;
    }

    public function getProxyApply($user_id)
    {
        return ProxyApply::where('user_id', '=', $user_id)->where('state', '!=', 3)->first();
    }

    public function getUserProxyApplyCount($user)
    {
        return ProxyApply::where('user_id', '=', $user)->where('state', '!=', 3)->count();
    }

    public function getProxyApplies($name = '', $phone = '', $state = 0, $page = 1, $limit = 10)
    {
        $db = DB::table('proxy_applies');
        if ($name) {
            $db->where('name', 'like', '%' . $name . '%');
        }
        if ($phone) {
            $db->orWhere('phone', 'like', '%' . $phone . '%');
        }

        if ($state) {
            $db->where('state', '=', $state);
        }
        $count = $db->count();
        $data = $db->limit($limit)->offset(($page - 1) * $limit)->get();
        return [
            'data' => $data,
            'count' => $count
        ];
    }

    public function checkProxyUser($user_id)
    {
        return ProxyUser::where('user_id', '=', $user_id)->count();
    }

    public function passProxyApply($id)
    {
        $proxy = ProxyApply::findOrFail($id);
        if ($proxy->state != 1) {
            return false;
        }
        $proxy->state = 2;
        $proxy->save();
        $user = new ProxyUser();
        $user->user_id = $proxy->user_id;
        $user->name = $proxy->name;
        $user->phone = $proxy->phone;
        $user->save();
        $amount = new UserAmount();
        $amount->user_id = $proxy->user_id;
        $amount->save();
        return true;
    }

    public function rejectProxyApply($id)
    {
        $proxy = ProxyApply::findOrFail($id);
        if ($proxy->state != 1) {
            return false;
        }
        $proxy->state = 3;
        $proxy->save();
        return true;
    }

    public function getProxyUsers($name = '', $phone = '', $page = 1, $limit = 10)
    {
        $db = DB::table('proxy_users');
        if ($name) {
            $db->where('name', 'like', '%' . $name . '%');
        }
        if ($phone) {
            $db->orWhere('phone', 'like', '%' . $phone . '%');
        }
        $count = $db->count();
        $data = $db->limit($limit)->offset(($page - 1) * $limit)->get();
        $this->formatProxyUsers($data);
        return [
            'count' => $count,
            'data' => $data
        ];
    }

    public function formatProxyUsers(&$proxies)
    {
        if (empty($proxies)) {
            return [];
        }
        foreach ($proxies as $proxy) {
            $proxy->user = WeChatUser::find($proxy->user_id);
        }
        return $proxies;
    }

    public function addWithdrawApply($id = 0, $data)
    {
        if ($id) {
            $apply = WithdrawApply::find($id);
        } else {
            $apply = new WithdrawApply();
        }
        foreach ($data as $key => $value) {
            $apply->$key = $value;
        }
        if ($apply->save()) {
            return true;
        }
        return false;
    }

    public function getWithdrawApplies($name, $page, $limit, $user_id = 0)
    {
        $db = DB::table('withdraw_applies');
        if ($name) {
            $db->where('name', 'like', '%' . $name . '%');
        }
        if ($user_id) {
            $db->where('user_id', '=', $user_id);
        }
        $count = $db->count();
        $data = $db->orderBy('id', 'DESC')->limit($limit)->offset(($page - 1) * $limit)->get();
        return [
            'data' => $data,
            'count' => $count
        ];
    }

    public function getWithdrawApply($id)
    {
        return WithdrawApply::findOrFail($id);
    }

    public function addBrokerageQueue($order_id)
    {
        $queue = BrokerageQueue::where('order_id', '=', $order_id)->first();
        if (empty($queue)) {
            $queue = new BrokerageQueue();
            $queue->order_id = $order_id;
            $queue->save();
        }
        return true;
    }

    public function delBrokerageQueue($order_id)
    {
        $queue = BrokerageQueue::where('order_id', '=', $order_id)->first();
        if (empty($queue)) {
            return true;
        }
        $queue->delete();
        return true;
    }

    public function getBrokerageRatio()
    {
        $ratio = BrokerageRatio::first();
        return $ratio;
    }

    public function addBrokerageRatio($data)
    {
        $ratio = BrokerageRatio::first();
        if (empty($ratio)) {
            $ratio = new BrokerageRatio();
        }
        foreach ($data as $key => $value) {
            $ratio->$key = $value;
        }
        if ($ratio->save()) {
            return true;
        }
        return false;
    }

    public function getUserBrokerageList($user, $page = 1, $limit = 10)
    {
        $count = Brokerage::where('user_id', '=', $user)->count();
        $data = Brokerage::where('user_id', '=', $user)->limit($limit)->offset(($page - 1) * $limit)->get();
        return [
            'data' => $data,
            'count' => $count
        ];
    }

    public function getBrokerageList($page = 1, $limit = 10)
    {
        $count = Brokerage::count();
        $data = Brokerage::limit($limit)->offset(($page - 1) * $limit)->get();
        return [
            'data' => $data,
            'count' => $count
        ];
    }

    public function formatUserBrokerageList(&$brokerages)
    {
        if (empty($brokerages)) {
            return [];
        }
        foreach ($brokerages as $brokerage) {
            $brokerage->order = Order::find($brokerage->order_id);
        }
        return $brokerages;
    }
}