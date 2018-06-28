<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\ProductCategoryPost;
use App\Http\Requests\ProductTypePost;
use App\Modules\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
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
        $parent = Input::get('parent',0);
        $types = $this->handle->getProductTypes($page,$limit,$title,$level,$parent);
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
    public function addProductCategory(ProductCategoryPost $post)
    {
        $categories = $post->categories;
        $type_id = $post->type_id;
        foreach ($categories as $category){
            $id = isset($category['id'])?$category['id']:0;
            $detail = $category['detail'];
            $detail = array_column($detail,'content');
            $this->handle->addProductCategory($id,$type_id,$category['title'],Auth::id(),$detail);
        }
        return jsonResponse([
            'msg'=>'ok'
        ],200);
    }
    public function delProductCategory()
    {
        $id = Input::get('id');
        if ($this->handle->delProductCategory($id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'参数错误！'
        ],400);
    }
    public function getProductCategories()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $title = Input::get('title','');
        $data = $this->handle->getProductCategories(getStoreId(),$page,$limit,$title);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function addProduct(Request $post)
    {
        $id = $post->id?$post->id:0;
        $data = [
            'store_id'=>getStoreId(),
            'name'=>$post->name,
            'detail'=>$post->detail,
            'brokerage'=>$post->brokerage,
            'express'=>$post->express,
            'express_price'=>$post->express_price,
            'share_title'=>$post->share_title,
            'share_detail'=>$post->share_detail,
            'type_id'=>$post->type_id
        ];
        $stock = $post->stock;
        $product_id = $this->handle->addProduct($id,$data);
        if ($product_id){
            foreach ($stock as $item){
                foreach ($item['detail'] as $detail){
                    $this->handle->addProductCategorySnapshot($product_id,$detail);
                }
                $detail = $item['detail'];
                sort($detail);
                $stockData = [
                    'cover'=>$item['cover'],
                    'price'=>$item['price'],
                    'origin_price'=>$item['origin_price'],
                    'detail'=>$detail
                ];
                $images = $item['images'];
                $stock_id = $this->handle->addStock($id,$stockData);
                foreach ($images as $image){
                    $this->handle->addStockImage($stock_id,$image);
                }
            }
        }
        return jsonResponse([
            'msg'=>'ok'
        ]);
    }


}
