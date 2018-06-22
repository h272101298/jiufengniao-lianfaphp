<?php

namespace App\Http\Controllers\V1;

use App\Modules\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class StoreController extends Controller
{
    //
    private $handle;
    public function __construct()
    {
        $this->handle = new User();
    }
    public function addStoreCategory()
    {
        $title = Input::get('title');
        $id = Input::get('id',0);
        if ($this->handle->addStoreCategory($id,$title)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'添加失败！'
        ],400);
    }
    public function getStoreCategories()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $categories = $this->handle->getStoreCategories($page,$limit);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$categories
        ]);
    }
    public function deletesStoreCategory()
    {
        $id = Input::get('id');
        if ($this->handle->delStoreCategory($id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'删除失败！'
        ],400);
    }
}
