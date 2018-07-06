<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\AddressPost;
use App\Http\Requests\ApplyPost;
use App\Http\Requests\WXLogin;
use App\Libraries\Wxxcx;
use App\Modules\Order\Model\Refuse;
use App\Modules\Proxy\Model\ProxyList;
use App\Modules\Proxy\Model\ProxyUser;
use App\Modules\WeChatUser\Model\WeChatUser;
use App\Modules\WeChatUser\WeChatUserHandle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use function Psy\debug;

class WeChatController extends Controller
{
    private $handle;
    public function __construct()
    {
        $this->handle = new WeChatUserHandle();
    }
    //
    /**
     * 微信用户登录
     */
    public function login(WXLogin $post)
    {
        $WX = getWxXcx();
        $sessionKey = $WX->getSessionKey($post->code);
        $proxy_id = $post->proxy_id?$post->proxy_id:0;
        if ($sessionKey){
            $info = $WX->decode($post->encryptedData,$post->iv);
            $info = json_decode($info);
            $user = $this->handle->findUserByOpenId($info->openId);
            if ($user){
                $token = CreateNonceStr(8);
                setRedisData($token,$user->id);
                return \jsonResponse([
                    'msg'=>'ok',
                    'data'=>[
                        'token'=>$token,
                        'apply'=>$this->handle->getUserSettleApplyCount($token),
                        'address'=>$this->handle->getDefaultAddress($user->id),
                        'is_proxy'=>$this->handle->checkProxyUser($user->id),
                        'proxy_apply'=>$this->handle->getUserProxyApplyCount($user->id)
                    ]
                ]);
            }else{
                $userData = [
                    'open_id'=>$info->openId,
                    'nickname'=>$info->nickName,
                    'gender'=>$info->gender,
                    'city'=>$info->city,
                    'province'=>$info->province,
                    'avatarUrl'=>$info->avatarUrl,
                ];
                $userId = $this->handle->createUser($userData);
                if ($userId){
                    $this->handle->addProxyList($userId,$proxy_id);
                    $token = CreateNonceStr(8);
                    setRedisData($token,$userId);
                    return \jsonResponse([
                        'msg'=>'ok',
                        'data'=>[
                            'token'=>$token,
                            'apply'=>0,
                            'address'=>'',
                            'is_proxy'=>0,
                            'proxy_apply'=>0
                        ]
                    ]);
                }
            }
        }else{
            return \jsonResponse([
                'msg'=>'ERROR',
                'data'=>$WX
            ],400);
        }
    }
    public function createApply(ApplyPost $post)
    {
        $token = $post->token;
        if ($this->handle->getUserSettleApplyCount($token)>0){
            return \jsonResponse([
                'msg'=>'有待审核的申请!'
            ],400);
        }
        if ($this->handle->countSettleApplyPhone($post->phone)!=0){
            return \jsonResponse([
                'msg'=>'该手机号码已被使用!'
            ],400);
        }
        $data = [
            'name'=>$post->name,
            'phone'=>$post->phone,
            'city'=>implode(',',$post->city),
            'storeName'=>$post->storeName,
            'type'=>$post->type,
            'category'=>$post->category,
            'notifyId'=>$post->notifyId,
            'picture'=>$post->picture?$post->picture:''
        ];
        if ($this->handle->createSettleApply($token,$data)){
            return \jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return \jsonResponse(['msg'=>'error'],400);
    }

    /**
     * 添加收货地址，需登录
     */
    public function createAddress(AddressPost $post)
    {
        $token = $post->token;
        $id = $post->id?$post->id:0;
        $data = [
            'city'=>implode(',',$post->city),
            'name'=>$post->name,
            'phone'=>$post->phone,
            'address'=>$post->address,
            'zip_code'=>$post->zipCode
        ];
        if ($this->handle->addAddress($token,$data,$id)){
            return \jsonResponse([
                'msg'=>'ok'
            ]);
        }
    }

    /**
     * 返回地址列表
     */
    public function getAddresses()
    {
        $uid = getRedisData(Input::get('token'));
        $addresses = $this->handle->listMyAddress($uid);
        if (!empty($addresses)){
            foreach ($addresses as $address){
                $address->city = explode(',',$address->city);
                $address->is_default = $this->handle->isDefaultAddress($address->id);
            }
        }
        return \jsonResponse([
            'msg'=>'ok',
            'data'=>$addresses
        ]);
    }
    /**
     * 删除地址
     */
    public function delAddress()
    {
        $token = Input::get('token');
        $id = Input::get('id');
        if ($this->handle->delAddress($token,$id)){
            return \jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return \jsonResponse([
            'msg'=>'删除失败！'
        ],400);
    }
    /**
     * 获取单个地址
     */
    public function getAddress()
    {
        $id = Input::get('id');
        $address = $this->handle->getAddress($id);
        $address->city = explode(',',$address->city);
        return \jsonResponse([
            'msg'=>'ok',
            'data'=>$address
        ]);
    }
    /**
     * 设置默认地址
     */
    public function setDefaultAddress()
    {
        $id = Input::get('id');
        $token = Input::get('token');
        if ($this->handle->setDefaultAddress($token,$id)){
            return \jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return \jsonResponse([
            'msg'=>'设置失败！'
        ],400);
    }
    public function getDefaultAddress()
    {
        $user_id = getRedisData(Input::get('token'));
        $address = $this->handle->getDefaultAddress($user_id);
        return \jsonResponse([
            'msg'=>'ok',
            'data'=>$address
        ]);
    }
    public function addProxyApply(Request $post)
    {
        $user_id = getRedisData($post->token);
        $count = $this->handle->getUserProxyApplyCount($user_id);
        if ($count!=0){
            return \jsonResponse([
                'msg'=>'有待审核的申请！'
            ],400);
        }
        $data = [
            'user_id'=>$user_id,
            'name'=>$post->name,
            'phone'=>$post->phone,
            'bank'=>$post->bank,
            'account'=>$post->account
        ];
        if ($this->handle->addProxyApply(0,$data)){
            return \jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return \jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function addWithdrawApply(Request $post)
    {
        $user_id = getRedisData($post->token);
        $amount = $this->handle->getUserAmount($user_id);
        if ($amount==0||$amount<$post->price){
            return \jsonResponse([
                'msg'=>'账户余额不足！'
            ],400);
        }
        $data = [
            'user_id'=>$user_id,
            'price'=>$post->price,
            'name'=>$post->name,
            'bank'=>$post->bank,
            'account'=>$post->account
        ];
        if ($this->handle->addWithdrawApply(0,$data)){
            $this->handle->addUserAmount($user_id,0-$post->price);
            return \jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return \jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function getUserAmount()
    {
        $user_id = getRedisData(Input::get('token'));
        $amount = $this->handle->getUserAmount($user_id);
        return \jsonResponse([
            'msg'=>'ok',
            'data'=>$amount
        ]);
    }
    public function getWechatUsers()
    {
        $name = Input::get('name');
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $users = $this->handle->listUsers($name,$page,$limit);
        $this->handle->formatUsers($users['data']);
        return \jsonResponse([
            'msg'=>'ok',
            'data'=>$users
        ]);
    }
    public function getWithdrawApplies()
    {
        $user_id = getRedisData(Input::get('token'));
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $data = $this->handle->getWithdrawApplies('',$page,$limit,$user_id);
        return \jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function getUserQrCode()
    {
        $user_id = getRedisData(Input::get('token'));
        $wx =  new Wxxcx(config('weChat.appId'),config('weChat.appSecret'));
        $data = array(
            'scene'=>'proxy='.$user_id,
            'page' => 'pages/index/index'
        );
        $data = json_encode($data);
        $token = $wx->getAccessToken();
        $qrcode = $wx->get_http_array('https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$token['access_token'],$data,'json');
        return response()->make($qrcode,200,['content-type'=>'image/gif']);
    }
    public function addUserInfo(Request $post)
    {
        $user_id = getRedisData($post->token);
        $data = [
            'user_id'=>$user_id,
            'phone'=>$post->phone,
            'name'=>$post->name,
            'sex'=>$post->sex,
            'email'=>$post->email
        ];
        if ($this->handle->addUserInfo(0,$data)){
            return \jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return \jsonResponse([
            'msg'=>'系统错误！'
        ]);
    }
    public function getUserInfo()
    {
        $user_id = getRedisData(Input::get('token'));
        $data = $this->handle->getUserInfoByUserId($user_id);
        return \jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function getProxyList()
    {
        $user_id = getRedisData(Input::get('token'));
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $data = $this->handle->getUserProxyList($user_id,$page,$limit);
        return \jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function addProxyList()
    {
        $user_id = getRedisData(Input::get('token'));
        $proxy_id = Input::get('proxy_id');
        if ($this->handle->addProxyList($user_id,$proxy_id)){
            return \jsonResponse([
                'msg'=>'ok'
            ]);
        };
        return \jsonResponse([
            'msg'=>'参数错误！'
        ],400);
    }
    public function getBrokerageList()
    {
        $user_id = getRedisData(Input::get('token'));
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $data = $this->handle->getUserBrokerageList($user_id,$page,$limit);
        $this->handle->formatUserBrokerageList($data['data']);
        return \jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }

}
