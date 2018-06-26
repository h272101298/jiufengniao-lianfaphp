<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\AddressPost;
use App\Http\Requests\ApplyPost;
use App\Http\Requests\WXLogin;
use App\Libraries\Wxxcx;
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
        $WX = new Wxxcx(config('weChat.appId'),config('weChat.appSecret'));
        $sessionKey = $WX->getSessionKey($post->code);
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
                        'address'=>$this->handle->getDefaultAddress($user->id)
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
                    $token = CreateNonceStr(8);
                    setRedisData($token,$userId);
                    return \jsonResponse([
                        'msg'=>'ok',
                        'data'=>[
                            'token'=>$token,
                            'apply'=>0,
                            'address'=>''
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
}
