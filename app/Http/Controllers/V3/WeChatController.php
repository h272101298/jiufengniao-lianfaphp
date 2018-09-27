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
        $uid = getRedisData(Input::get('token'));
        $config = $this->handle->getPrizeConfig();
        if (!empty($config)){
            $this->handle->addUserScore2($uid,$config->share_score);
            $data = [
                'user_id'=>$uid,
                'type'=>4,
                'score'=>$config->share_score,
                'remark'=>'分享获得'
            ];
            $this->handle->addScoreRecord(0,$data);
        }
        return jsonResponse([
            'msg'=>'ok'
        ]);
    }
}
