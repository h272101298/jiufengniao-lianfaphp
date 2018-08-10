<?php

namespace App\Http\Controllers\V3;

use App\Modules\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class SignController extends Controller
{
    //
    private $handle;
    public function __construct()
    {
        $this->handle = new User();
    }

    public function sign()
    {
        $user_id = getRedisData(Input::get('token'));
        if ($this->handle->checkSign($user_id)){
            return jsonResponse([
                'msg'=>'今天已签到！ '
            ],400);
        }
        $continue = $this->handle->getContinueSign($user_id);
        if ($continue){
            if ($continue['date']==date('Y-m-d',strtotime('-1 days'))){
                if ($continue['count']!=7){
                    $count = $continue['count']+1;
                }else{
                    $count = 1;
                }
                $continue['count'] = $count;
                $continue['date'] = date('Y-m-d',time());
                $this->handle->setContinueSign($user_id,$continue);
            }else{
                $count = 1;
                $continue['count'] = $count;
                $continue['date'] = date('Y-m-d',time());
                $this->handle->setContinueSign($user_id,$continue);
            }
        }else{
            $count = 1;
            $continue['count'] = $count;
            $continue['date'] = date('Y-m-d',time());
            $this->handle->setContinueSign($user_id,$continue);
        }
        $this->handle->addSignRecord($user_id);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$count
        ]);
    }
    public function getSignRecords()
    {
        $user_id = getRedisData(Input::get('token'));
        $count = $this->handle->checkSign($user_id,0);
        $check = $this->handle->checkSign($user_id,1);
        $continue = $this->handle->getContinueSign($user_id);
        if ($continue){
            if ($continue['date']==date('Y-m-d',time())||$continue['date']==date('Y-m-d',strtotime('-1 days'))){
                $days = $continue['count'];
            }else{
                $days = 0;
            }
        }else{
            $days = 0;
        }
        return jsonResponse([
            'msg'=>'ok',
            'data'=>[
                'count'=>$count,
                'continue'=>$days,
                'check'=>$check
            ]
        ]);
    }
    public function setSignConfigs(Request $post)
    {
        $configs = $post->configs;
        if (!empty($configs)){
            foreach ($configs as $config){
                $info = $this->handle->getSignConfig($config['days']);
                if ($info){
                    $id = $info->id;
                }else{
                    $id = 0;
                }
                $data = [
                    'days'=>$config['days'],
                    'type'=>$config['type'],
                    'reward'=>$config['reward']
                ];
                $this->handle->addSignConfig($id,$data);
            }
        }
        return jsonResponse([
            'msg'=>'ok'
        ]);
    }
    public function getSignConfigs()
    {
        $data = $this->handle->getSignConfigs();
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
}
