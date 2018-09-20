<?php

namespace App\Http\Controllers\V2;

use App\Modules\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class WeChatController extends Controller
{
    //
    private $handle;
    public function __construct()
    {
        $this->handle = new User();
    }
    public function countPromotions()
    {
        $user_id = getRedisData(Input::get('token'));
        $type = Input::get('type');
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        switch ($type){
            case 'card':
                $data = $this->handle->getUserJoinPromotions($user_id,$page,$limit);
                $this->handle->formatUserJoinPromotions($data['data'],$user_id);
                break;
            case 'bargain':
                $data = $this->handle->getBargainLists($user_id,$page,$limit);
                $this->handle->formatBargainRecords($data['data'],1);
                break;
        }
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function member()
    {
        $user_id = getRedisData(Input::get('token'));
        $member = $this->handle->getMemberUser($user_id);
        $this->handle->formatMemberUser($member);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$member
        ]);
    }

}
