<?php

namespace App\Http\Controllers\V3;

use App\Modules\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class ScoreController extends Controller
{
    //初始化控制器
    private $handle;
    public function __construct()
    {
        $this->handle = new User();
    }
    //添加积分商品
    public function addScoreProduct(Request $post)
    {
        $id = $post->id?$post->id:0;
        $store_id = getStoreId();
        $data = [
            'name'=>$post->name,
            'description'=>$post->description,
            'detail'=>$post->detail,
            'share_title'=>$post->share_title,
            'norm'=>$post->norm,
            'store_id'=>$store_id
        ];
        $stocks = $post->stocks;
        $norm = $post->norm;
        $product_id = $this->handle->addScoreProduct($id,$data);
        if ($product_id){
            foreach ($stocks as $item){
                if ($norm!='fixed'){
                    $swap = [];
                    if (isset($item['product_detail'])){
                        $detail = $item['product_detail'];
                    }
                    if (isset($item['details'])){
                        foreach ($item['details'] as $detail){
                            $detail_id = $this->handle->addScoreProductCategorySnapshot($product_id,$detail);
                            array_push($swap,$detail_id);
                        }
                        sort($swap);
                        $detail = implode(',',$swap);
                    }
                }else{
                    $detail = 'fixed';
                }
                $stockData = [
                    'product_id'=>$product_id,
                    'cover'=>$item['cover'],
                    'score'=>$item['score'],
                    'origin_price'=>$item['origin_price'],
                    'product_detail'=>$detail
                ];
                $images = $item['images'];
                $stockId = isset($item['id'])&&$item!=0?$item['id']:0;
//                $this->handle->delStocks($product_id);
                $stock_id = $this->handle->addScoreProductStock($stockId,$stockData);
                $this->handle->delScoreStockImages($stock_id);
                foreach ($images as $image){
                    $this->handle->addScoreStockImage($stock_id,$image);
                }
            }
        }
        return jsonResponse([
            'msg'=>'ok'
        ]);
    }
    public function getScoreProducts()
    {
        $state = Input::get('state');
        $name = Input::get('name');
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $data = $this->handle->getScoreProducts($page,$limit,1);
        $this->handle->formatScoreProducts($data['data']);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function getAllScoreProducts()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $hot = Input::get('hot',0);
        $data = $this->handle->getScoreProducts($page,$limit,1,0,2,$hot);
        $this->handle->formatScoreProducts($data['data']);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function reviewScoreProduct()
    {
        $id = Input::get('id');
        $data = [
            'review'=>1
        ];
        if ($this->handle->addScoreProduct($id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ]);
    }
    public function delScoreProduct()
    {
        $id = Input::get('id');
        $data = [
            'deleted'=>1
        ];
        if ($this->handle->addScoreProduct($id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ]);
    }
    public function hotScoreProduct()
    {
        $id = Input::get('id');
        $product = $this->handle->getScoreProduct($id);
        $data = [
            'hot'=>$product->hot==0?1:0
        ];
        if ($this->handle->addScoreProduct($id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ]);
    }
    public function enableScoreProduct()
    {
        $id = Input::get('id');
        $product = $this->handle->getScoreProduct($id);
        $data = [
            'state'=>$product->state==0?1:0
        ];
        if ($this->handle->addScoreProduct($id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ]);
    }
    public function getScoreProduct()
    {
        $id = Input::get('id');
        $product = $this->handle->getScoreProduct($id);
        $product->store = $this->handle->getStoreById($product->store_id);
        $product->stocks = $this->handle->getScoreProductStocks($id,1);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$product
        ]);
    }
    public function getScoreProductApi()
    {
        $id = Input::get('id');
        $product = $this->handle->getScoreProduct($id);
        $product->categories = $this->handle->getScoreProductCategories($id);
        $product->store = $this->handle->getStoreById($product->store_id);
        $product->stocks = $this->handle->getScoreProductStocks($id);
        $product->express = $this->handle->getStoreExpress($product->store_id);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$product
        ]);
    }
    public function getScoreProductStock()
    {
        $product_id = Input::get('product_id');
        $detail = Input::get('detail');
        if ($detail){
            $detail = explode(',',$detail);
            sort($detail);
            $detail = array_filter($detail,function ($item){
                return $item!='';
            });
            $detail = implode(',',$detail);
        }
        $stock = $this->handle->getScoreStock($product_id,$detail);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$stock
        ]);
    }
    public function getScoreConfig()
    {
        $config = $this->handle->getScoreConfig();
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$config
        ]);
    }
    public function setScoreConfig()
    {
        $state = Input::get('state',0);
        $ratio = Input::get('ratio',1);
        if ($this->handle->setScoreConfig($state,$ratio)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function getScoreRecords()
    {
        $type = Input::get('type',0);
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $user_id = getRedisData(Input::get('token'));
        $data = $this->handle->getScoreRecords($user_id,$type,$page,$limit);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
}
