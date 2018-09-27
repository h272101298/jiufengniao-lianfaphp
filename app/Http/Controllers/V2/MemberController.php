<?php

namespace App\Http\Controllers\V2;

use App\Libraries\WxPay;
use App\Modules\Member\Model\MemberRecord;
use App\Modules\User;
use function GuzzleHttp\Psr7\uri_for;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class MemberController extends Controller
{
    //
    private $handle;
    public function __construct()
    {
        $this->handle = new User();
    }
    public function addMemberLevel(Request $post)
    {
        $id = $post->id?$post->id:0;
        $data = [
            'name'=>$post->name,
            'price'=>$post->price,
            'time'=>$post->time,
            'discount'=>$post->discount,
            'detail'=>$post->detail
        ];
        if ($this->handle->addMemberLevel($id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function getMemberLevels()
    {
        $name = Input::get('name');
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $data = $this->handle->getMemberLevels($name,$page,$limit);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function delMemberLevel()
    {
        $id = Input::get('id');
//        $count = $this->handle->countMemberUsers($id);
//        if ($count!=0){
//            return jsonResponse([
//                'msg'=>'存在该等级会员！'
//            ],400);
//        }
        if ($this->handle->delMemberLevel($id)){
            $this->handle->delMemberUsers($id);
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function addMemberUser()
    {
        $user_id = Input::get('user_id');
        $member_id = Input::get('member_id');
        $level = $this->handle->getMemberLevel($member_id);
        $member = $this->handle->getMemberUser($user_id);
        if (!empty($member)){
            $id = $member->id;
            if ($member->end>time()){
                $end = $member->end+$level->time*24*60*60;
            }else{
                $end = time()+$level->time*24*60*60;
            }
        }else{
            $id = 0;
            $end = time()+$level->time*24*60*60;
        }
        $data = [
            'user_id'=>$user_id,
            'level_id'=>$member_id,
            'end'=>$end,
            'discount'=>$level->discount
        ];
        if ($this->handle->addMemberUser($id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function getMemberUsers()
    {
        $name = Input::get('name');
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $user_ids = $this->handle->getWeChatUsersIdByName($name);
        $data = $this->handle->getMemberUsers($user_ids,0,$page,$limit);
        $this->handle->formatMemberUsers($data['data']);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function addMemberRecord(Request $post)
    {
        $url = $post->getScheme() . '://' . $post->getHttpHost() . '/api/member/notify';
        $user_id = getRedisData($post->token);
        $member_id = $post->member_id;
        $level = $this->handle->getMemberLevel($member_id);
        $number = self::makePaySn($user_id);
        $data = [
            'number'=>$number,
            'user_id'=>$user_id,
            'level_id'=>$member_id,
            'name'=>$level->name,
            'price'=>$level->price
        ];
        if ($this->handle->addMemberRecord(0,$data)){
            $user = $this->handle->getWeChatUserById($user_id);
            $wxPay = getWxPay($user->open_id);
            $data = $wxPay->pay($number, '购买商品', ($level->price) * 100, $url);
            return jsonResponse([
                'msg' => 'ok',
                'data' => $data
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function memberNotify(Request $post)
    {
        $data = $post->getContent();
        $wx = WxPay::xmlToArray($data);
        $wspay = getWxPay($wx['openid']);
        $data = [
            'appid' => $wx['appid'],
            'cash_fee' => $wx['cash_fee'],
            'bank_type' => $wx['bank_type'],
            'fee_type' => $wx['fee_type'],
            'is_subscribe' => $wx['is_subscribe'],
            'mch_id' => $wx['mch_id'],
            'nonce_str' => $wx['nonce_str'],
            'openid' => $wx['openid'],
            'out_trade_no' => $wx['out_trade_no'],
            'result_code' => $wx['result_code'],
            'return_code' => $wx['return_code'],
            'time_end' => $wx['time_end'],
            'total_fee' => $wx['total_fee'],
            'trade_type' => $wx['trade_type'],
            'transaction_id' => $wx['transaction_id']
        ];
        $sign = $wspay->getSign($data);
        if ($sign == $wx['sign']) {
            $order = MemberRecord::where(['number' => $wx['out_trade_no']])->first();
            $user_id = $order->user_id;
            $member_id = $order->level_id;
            $level = $this->handle->getMemberLevel($member_id);
            $member = $this->handle->getMemberUser($user_id);
            if (!empty($member)){
                $id = $member->id;
                if ($member->end>time()){
                    $end = $member->end+$level->time*24*60*60;
                }else{
                    $end = time()+$level->time*24*60*60;
                }
            }else{
                $id = 0;
                $end = time()+$level->time*24*60*60;
            }
            $data = [
                'user_id'=>$user_id,
                'level_id'=>$member_id,
                'end'=>$end,
                'discount'=>$level->discount
            ];
            if ($this->handle->addMemberUser($id,$data)){
                $this->handle->addMemberRecord($order->id,['state'=>'finished']);
                return 'SUCCESS';
            }
            return 'ERROR';
        }
        return 'ERROR';

    }
    public function getMemberRecords()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $data = $this->handle->getMemberRecords(0,$page,$limit);
        $this->handle->formatMemberRecords($data['data']);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
}
