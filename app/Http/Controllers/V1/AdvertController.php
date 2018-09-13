<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\AdvertPost;
use App\Modules\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;

class AdvertController extends Controller
{
    //
    private $handle;
    public function __construct()
    {
        $this->handle = new User();
//        dd($this->handle);
    }

    /**
     * 添加广告
     */
    public function addAdvert(Request $post)
    {
//        return response()
        $id = $post->id?$post->id:0;
        $category_id = $post->category_id?$post->category_id:0;
        $product_id = $post->product_id ? $post->product_id : 0;
        $type = $post->type? $post->type:0;
        $data = [
            'type'=>$type,
            'url'=>$post->url?$post->url:'',
            'pic'=>$post->pic,
            'detail'=>$post->detail?$post->detail:'',
            'product_id'=>$product_id
        ];
        $result = $this->handle->createAdvert($id,$data,$category_id);
        if ($result){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'保存失败！'
        ],400);
    }

    /**
     * 获取所有广告
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAdverts()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $type = Input::get('type',0);
        $categoryId = Input::get('category_id');
        $adverts = $this->handle->getAdverts($page,$limit,$type,$categoryId);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$adverts
        ]);
    }

    /**
     * 删除广告
     */
    public function delAdvert()
    {
        $id = Input::get('id');
        if ($this->handle->delAdvert($id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'删除失败！'
        ],400);
    }

}
