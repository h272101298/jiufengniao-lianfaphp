<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\ProductTypePost;
use App\Modules\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class ProductController extends Controller
{
    //
    private $handle;
    public function __construct()
    {
        $this->handle = new User();
    }
    public function createProductType(ProductTypePost $post)
    {
        $id = $post->id?$post->id:0;
        $parent = $post->parent?$post->parent:0;
        $data = [
            'title'=>$post->title,
            'logo'=>$post->logo?$post->logo:''
        ];
        if ($this->handle->addProductType($id,$data,$parent)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'操作失败！'
        ],400);
    }
    public function getProductTypes()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $title = Input::get('title');
        $level = Input::get('level',0);
        $types = $this->handle->getProductTypes($page,$limit,$title,$level);
        return response()->json([
            'msg'=>'ok',
            'data'=>$types
        ]);
    }
    public function delProductType()
    {
        $id = Input::get('id');
        if ($this->handle->delProductType($id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'操作失败！'
        ],400);
    }
    public function getProductTypesTree()
    {
        $data = $this->handle->getProductTypesTree();
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
}
