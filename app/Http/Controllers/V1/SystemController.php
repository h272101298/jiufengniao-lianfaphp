<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\DocumentPost;
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

    public function getDocuments()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $title = Input::get('title','');
        $data = $this->handle->getDocuments($page,$limit,$title);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function createDocument(DocumentPost $post)
    {
        $id = $post->id?$post->id:0;
        if ($this->handle->createDocument($id,$post->title,$post->detail)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'参数错误！'
        ],400);
    }
    public function delDocument()
    {
        $id = Input::get('id');
        if ($this->handle->delDocument($id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        };
        return jsonResponse([
            'msg'=>'操作失败！'
        ],400);
    }
    public function addPermission()
    {
        $id = Input::get('id',0);
        $data = [
            'name'=>Input::get('name'),
            'display_name' => Input::get('display_name')
        ];
        if ($this->handle->createPermission($id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
    }
    public function getPermissions()
    {
        $permissions = $this->handle->getPermissions();
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$permissions
        ]);

    }
    public function addRole()
    {
        $id = Input::get('id',0);
        $data = [
            'name'=>Input::get('name'),
            'display_name' => Input::get('display_name')
        ];
        $permissions = Input::get('permissions');
        if ($this->handle->createRole($id,$data,$permissions)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
    }
    public function getRoles()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $name = Input::get('name');
        $data = $this->handle->getRoles($page,$limit,$name);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function delRole()
    {
        $id = Input::get('id');
        if($this->handle->delRole($id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
    }
    public function addBrokerageRatio(Request $post)
    {
        $data = [
            'system'=>$post->system,
            'level1'=>$post->level1,
            'level2'=>$post->level2,
            'level3'=>$post->level3
        ];
        if ($this->handle->addBrokerageRatio($data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        };
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function getBrokerageRatio()
    {
        $data = $this->handle->getBrokerageRatio();
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function addTxConfig(Request $post)
    {
        $data = [
            'app_id'=>$post->app_id,
            'app_secret'=>$post->app_secret,
            'api_key'=>$post->api_key,
            'mch_id'=>$post->mch_id,
            'ssl_cert'=>$post->ssl_cert,
            'ssl_key'=>$post->ssl_key
        ];
        if ($this->handle->addTxConfig($data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function getTxConfig()
    {
        $data = $this->handle->getTxConfig();
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
}
