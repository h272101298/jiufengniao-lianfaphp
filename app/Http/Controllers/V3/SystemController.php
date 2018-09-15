<?php

namespace App\Http\Controllers\V3;

use App\Modules\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class SystemController extends Controller
{
    //
    private $handle;
    public function __construct()
    {
        $this->handle = new User();
    }

    public function addAmountConfig()
    {
        $key = Input::get('title');
        $value = Input::get('content');
        if ($this->handle->setAmountConfig($key,$value)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        };
    }
    public function addPickUpConfig()
    {
        $state = Input::get('state',0);
        if ($this->handle->setPickUpConfig($state)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function getPickUpConfig()
    {
        $data = $this->handle->getPickUpConfig();
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function makeQrcode(Request $post)
    {
        $data = $post->all();
        $page = $data['page'];
        unset($data['page']);
        $scene = '';
        foreach ($data as $key =>$value){
            $scene .=$key.'='.$value.'&';
        }
        $scene = substr($scene,0,-1);
//        dd($scene);
        $wx =  getWxXcx();
        $data = [
            'scene'=>$scene,
            'page'=>$page
        ];
        $data = json_encode($data);
        $token = $wx->getAccessToken();
        $qrcode = $wx->get_http_array('https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$token['access_token'],$data,'json');
        return response()->make($qrcode,200,['content-type'=>'image/gif']);
    }
}
