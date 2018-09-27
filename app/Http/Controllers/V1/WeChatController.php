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
                $count = getRedisData('LoginCount',0);
                $time = strtotime(date('Y-m-d').' 23:59:59')-time();
                setRedisData('LoginCount',$count+1,$time);
                return \jsonResponse([
                    'msg'=>'ok',
                    'data'=>[
                        'token'=>$token,
                        'user_id'=>$user->id,
                        'proxy_id'=>$proxy_id,
                        'address'=>$this->handle->getDefaultAddress($user->id),
                        'apply'=>$this->handle->getUserSettleApplyCount($token),
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
                    if ($proxy_id!=0){
                        $this->handle->addProxyList($userId,$proxy_id);
                    }
                    $config = $this->handle->getPrizeConfig();
                    if (!empty($config)){
                        $this->handle->addUserScore2($userId,$config->share_score);
                        $data = [
                            'user_id'=>$userId,
                            'type'=>3,
                            'score'=>$config->register_score,
                            'remark'=>'注册获得'
                        ];
                        $this->handle->addScoreRecord(0,$data);
                    }
                    $token = CreateNonceStr(8);
                    setRedisData($token,$userId);
                    $count = getRedisData('LoginCount',0);
                    $time = strtotime(date('Y-m-d').' 23:59:59')-time();
                    setRedisData('LoginCount',$count+1,$time);
                    return \jsonResponse([
                        'msg'=>'ok',
                        'data'=>[
                            'token'=>$token,
                            'user_id'=>$userId,
                            'proxy_id'=>$proxy_id,
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
    public function test()
    {
        $user_id = 2;
        $proxy_id = 1;
        $this->handle->addProxyList($user_id,$proxy_id);
        return \jsonResponse([
            'msg'=>'ok'
        ]);
    }
    public function getProxyInfo()
    {
        $token = Input::get('token');
        $user_id = getRedisData($token);
        return \jsonResponse([
            'msg'=>'ok',
            'data'=>[
                'apply'=>$this->handle->getUserSettleApplyCount($token),
                'is_proxy'=>$this->handle->checkProxyUser($user_id),
                'proxy_apply'=>$this->handle->getUserProxyApplyCount($user_id)
            ]
        ]);
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

    /**
     * 获取默认地址
     * @return JsonResponse
     */
    public function getDefaultAddress()
    {
        $user_id = getRedisData(Input::get('token'));
        $address = $this->handle->getDefaultAddress($user_id);
        return \jsonResponse([
            'msg'=>'ok',
            'data'=>$address
        ]);
    }

    /**
     * @param Request $post
     * 录入代理申请
     * @return JsonResponse
     */
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
            'account'=>$post->account,
            'notify_id'=>$post->notifyId
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

    /**
     * @param Request $post
     * 添加提现申请
     * @return JsonResponse
     */
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
        $wx =  getWxXcx();
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
        $info = $this->handle->getUserInfoByUserId($user_id);
        $data = [
            'user_id'=>$user_id,
            'phone'=>$post->phone,
            'name'=>$post->name,
            'sex'=>$post->sex,
            'email'=>$post->email
        ];
        $id = empty($info)?0:$info->id;
        if ($this->handle->addUserInfo($id,$data)){
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
    public function addNotifyList()
    {
        $user_id = getRedisData(Input::get('token'));
        $notify_id = Input::get('notify_id');
        if ($this->handle->addNotifyList($user_id,$notify_id)){
            return \jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return \jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function getProxyApply()
    {
        $user_id = getRedisData(Input::get('token'));
        $data = $this->handle->getProxyApply($user_id);
        return \jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }

}
