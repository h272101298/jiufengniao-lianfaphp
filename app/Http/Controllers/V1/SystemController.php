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
}
