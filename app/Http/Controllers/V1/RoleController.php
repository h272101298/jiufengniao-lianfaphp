<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\RolePost;
use App\Modules\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class RoleController extends Controller
{
    private $handle;
    public function __construct()
    {
        $this->handle = new User();
    }

    /**
     * 新增角色
     */
    public function createRole(RolePost $post)
    {
        $id = $post->id;
        $data = [
            'name'=>$post->name,
            'display_name'=>$post->display_name
        ];
        if ($this->handle->createRole($data,$id)) {
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'保存错误！'
        ],400);
    }
    public function delRole()
    {
        $id = Input::get('id');
        if ($this->handle->delRole($id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'删除失败！'
        ],400);
    }
    public function getRoles()
    {

    }
}
