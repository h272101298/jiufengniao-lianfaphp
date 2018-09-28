<?php

namespace App\Http\Controllers\V3;

use App\Modules\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class WeChatController extends Controller
{
    //
    public function __construct()
    {
        $this->handle = new User();
    }
    public function share()
    {
        $shareId = Input::get('shareid');
        $uid = getRedisData(Input::get('token'));
        $page = Input::get('page');
        $config = $this->handle->getPrizeConfig();
        if ($shareId!=$uid){
            if (!empty($config)&&!$this->handle->checkShareRecord($uid,$shareId,$page)){
                $this->handle->addUserScore2($shareId,$config->share_score);
                $data = [
                    'user_id'=>$shareId,
                    'type'=>4,
                    'score'=>$config->share_score,
                    'remark'=>'分享获得'
                ];
                $this->handle->addScoreRecord(0,$data);
                $this->handle->addShareRecord($uid,$shareId,$page);
            }
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'Error',
        ],400);
    }
}
