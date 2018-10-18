<?php

namespace App\Http\Controllers\V3;

use App\Modules\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class GroupBuyController extends Controller
{
    private $handle;
    public function __construct()
    {
        $this->handle = new User();
    }

    //
    public function addGroupBuyPromotion(Request $post)
    {
        //$stock = $this->handle->getStockById($post->stock_id);
        $product = $this->handle->getProductById($post->product_id);
        $id = $post->id?$post->id:0;
        $data = [
            'store_id'=>$product->store_id,
            'product_id'=>$post->product_id,
            'title'=>$post->title,
            'time'=>$post->time,
            'people_number'=>$post->people_number,
            'free'=>$post->free
        ];
        $stocks = $post->stocks;
        $id = $this->handle->addGroupBuyPromotion($id,$data);
        if ($id){
            if (!empty($stocks)){
                foreach ($stocks as $item){
                    $stock = $this->handle->getStockById($item['stock_id']);
                    $data = [
                        'group_id'=>$id,
                        'stock_id'=>$item['stock_id'],
                        'price'=>$stock->price,
                        'group_price'=>$item['group_price']
                    ];
                    $this->handle->addGroupBuyStock(0,$data);
                }
            }
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function getGroupBuyPromotions()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $state = Input::get('state',1);
        $data = $this->handle->getGroupBuyPromotions(0,null,null,'',0,$state,0,$page,$limit);
        $this->handle->formatGroupBuyPromotions($data['data'],0,1,0,1);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function checkPromotion()
    {
        $id = Input::get('id');
        $data = ['state'=>Input::get('state',3)];
        if ($this->handle->addGroupBuyPromotion($id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ]);
    }
    public function getGroupBuyPromotion()
    {
        $id = Input::get('id');
        $promotion = $this->handle->getGroupBuyPromotion($id);
        $this->handle->formatGroupBuyPromotion($promotion,0,1);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$promotion
        ]);
    }
    public function modifyGroupBuyPromotion(Request $post)
    {
        $id = $post->id?$post->id:0;
        $data = [
            'title'=>$post->title,
//            'number'=>$post->number,
            'time'=>$post->time,
            //'start'=>strtotime($post->start),
            //'end'=>strtotime($post->end),
            'people_number'=>$post->people_number,
//            'origin_price'=>$post->origin_price,
//            'price'=>$post->price,
            'free'=>$post->free
        ];
        if ($this->handle->addGroupBuyPromotion($id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function enablePromotion()
    {
        $id = Input::get('id');
        $promotion = $this->handle->getGroupBuyPromotion($id);
        if ($promotion->state!=2) {
            return jsonResponse([
                'msg'=>'未审核的活动不能上线！'
            ],400);
        }
        $data = [
            'enable'=>$promotion->enable==0?1:0
        ];
        if ($this->handle->addGroupBuyPromotion($id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
    }
    public function addHotPromotion()
    {
        $id = Input::get('id');
        $promotion = $this->handle->getGroupBuyPromotion($id);
        $hot = $promotion->hot == 1 ? 0 : 1;
        if ($hot){
            $count = $this->handle->countGroupBuyPromotion(2,$id);
            if ($count>=4){
                return jsonResponse([
                    'msg'=>'超出数量限制！'
                ],400);
            }
        }
        $data = [
            'hot'=>$hot
        ];
        if ($this->handle->addGroupBuyPromotion($id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function delGroupBuyPromotion()
    {
        $id = Input::get('id');
        if ($this->handle->delGroupBuyPromotion($id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function getPromotions()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $free = Input::get('free',0);
        $hot = Input::get('hot',0);
        $data = $this->handle->getGroupBuyPromotions(0,null,null,'',$hot,2,2,$page,$limit,$free);
        $this->handle->formatGroupBuyPromotions($data['data'],1,1,1);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function getPromotion()
    {
        $id = Input::get('id');

        $user_id = getRedisData(Input::get('token'));
        $data = $this->handle->getGroupBuyPromotion($id);
        $data->collect = $this->handle->checkCollect($user_id,$data->product_id);
        $data->join = $this->handle->checkGroupJoin($id,$user_id);
        $this->handle->formatGroupBuyPromotion($data,1,1,1);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function getGroupBuyStock()
    {
        $group_id = Input::get('group_id');
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
        $stock = $this->handle->getStock($product_id,$detail);
        if (!empty($stock)){
            $data = $this->handle->getGroupBuyStock($group_id,$stock->id);
        }else{
            $data = [];
        }

        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function getOrderBuyList()
    {
        $id = Input::get('group_id');
        $data = $this->handle->getGroupBuyLists($id);
        $this->handle->formatGroupBuyLists($data['data']);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function getGroupBuyList()
    {
        $id = Input::get('id');
        $list = $this->handle->getGroupBuyList($id);
        $this->handle->formatGroupBuyList($list,1,0);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$list
        ]);
    }
    public function getMyGroupBuy()
    {
        $user_id = getRedisData(Input::get('token'));
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $free = Input::get('free',0);
        $idArray = $this->handle->getGroupBuyPromotionsId($free);
        if (empty($idArray)){
            $idArray = [0];
        }
//        dd($idArray);
        $data = $this->handle->getGroupBuyJoins($user_id,$idArray,$page,$limit);
        $this->handle->formatGroupBuyJoins($data['data']);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function getUserGroupFree()
    {
        $user_id = getRedisData(Input::get('token'));
        $free = $this->handle->getGroupFree($user_id);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$free
        ]);
    }
}
