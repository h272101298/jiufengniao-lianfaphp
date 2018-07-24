<?php

namespace App\Http\Controllers\V2;

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
        $count = $this->handle->countMemberUsers($id);
        if ($count!=0){
            return jsonResponse([
                'msg'=>'存在该等级会员！'
            ],400);
        }
        if ($this->handle->delMemberLevel($id)){
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
}
