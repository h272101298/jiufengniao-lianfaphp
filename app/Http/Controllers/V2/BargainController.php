<?php

namespace App\Http\Controllers\V2;

use App\Modules\User;
use function GuzzleHttp\Psr7\uri_for;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class BargainController extends Controller
{
    //
    private $handle;
    public function __construct()
    {
        $this->handle = new User();
    }

    public function createBargain(Request $post)
    {
        $product = $this->handle->getProductById($post->product_id);
        $id = $post->id?$post->id:0;
        $data = [
            'store_id'=>$product->store_id,
            'product_id'=>$product->id,
            //'stock_id'=>$stock->id,
            //'min_price'=>$post->min_price,
            //'origin_price'=>$post->origin_price,
            'clickNum'=>$post->clickNum,
            //'start'=>strtotime($post->start),
            //'end'=>strtotime($post->end),
            'time'=>$post->time,
            'description'=>$post->description,
            'number'=>$post->number
        ];
        $stocks = $post->stocks;
        $result = $this->handle->addBargainPromotion($id,$data);
        if ($result){
            foreach ($stocks as $stock){
                $swap = $this->handle->getStockById($stock['id']);
                $swapData = [
                    'origin_price'=>$swap->price,
                    'min_price'=>$stock['price'],
                    'bargain_id'=>$result,
                    'stock_id'=>$stock['id']
                ];
                $this->handle->addBargainStock(0,$swapData);
            }
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function modifyBargainPromotion(Request $post)
    {
        $id = $post->id;
        $data = [
            'description'=>$post->description,
            'number'=>$post->number,
            //'start'=>strtotime($post->start),
            //'end'=>strtotime($post->end),
            'time'=>$post->time,
            'clickNum'=>$post->clickNum,
            //'origin_price'=>$post->origin_price,
            //'min_price'=>$post->min_price
        ];
        if ($this->handle->addBargainPromotion($id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function getBargainPromotions()
    {
        $state = Input::get('state',0);
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $data = $this->handle->getBargainPromotions(null,0,$state,$page,$limit);
        $this->handle->formatBargainPromotions($data['data'],1);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function getBargainPromotion()
    {
        $id = Input::get('id');
        $data = $this->handle->getBargainPromotion($id);
        $this->handle->formatBargainPromotion($data);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function checkPromotion()
    {
        $id = Input::get('id');
        $data = ['state'=>Input::get('state',3)];
        if ($this->handle->addBargainPromotion($id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ]);
    }
    public function getEnablePromotions()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $hot = Input::get('hot',0);
        $user_id = getRedisData(Input::get('token'));
        $data = $this->handle->getBargainPromotions(null,0,0,$page,$limit,2,$hot);
        $this->handle->formatBargainPromotions($data['data'],1,$user_id);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function getEnablePromotion()
    {
        $id = Input::get('id');
        $user_id = getRedisData(Input::get('token'),0);
        $list_id = Input::get('list_id');
        if ($list_id){
            $join = $this->handle->getBargainListById($list_id);
        }else{
            $join = $this->handle->getBargainList($user_id,$id);
        }
        $list_id = !isset($join)?0:$join->id;
        $data = $this->handle->getBargainPromotion($id);
        $data->join = isset($join)?$join:null;
        $this->handle->formatBargainPromotion($data,0,$this->handle->checkUserBargain($user_id,$list_id),0,1);
        $data->store = $this->handle->getStoreById($data->store_id);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function enablePromotion()
    {
        $id = Input::get('id');
        $promotion = $this->handle->getBargainPromotion($id);
        if ($promotion->state!=2) {
            return jsonResponse([
                'msg'=>'未审核的活动不能上线！'
            ],400);
        }
        $data = [
            'enable'=>$promotion->enable==0?1:0
        ];
        if ($this->handle->addBargainPromotion($id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
    }
    public function getBargainPrice()
    {
        $promotion_id = Input::get('list_id');
        $price = $this->handle->getBargainPromotionPrice($promotion_id);
        $count = $this->handle->getBargainCount($promotion_id);
        $list = $this->handle->getBargainListById($promotion_id);
        $stock = $this->handle->getBargainStock($list->promotion_id,$list->stock_id);
//        $promotion = $this->handle->getBargainPromotion($promotion_id);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>[
                'price'=>sprintf('%.2f',$stock->origin_price-$price),
                'count'=>$count,
                'bargain_price'=>sprintf('%.2f',$price)
            ]
        ]);

    }
    public function bargain()
    {
        $promotion_id = Input::get('list_id');
        $list = $this->handle->getBargainListById($promotion_id);
        $user_id = getRedisData(Input::get('token'));
        if ($this->handle->checkUserBargain($user_id,$list->promotion_id)){
            return jsonResponse([
                'msg'=>'已经参加过砍价活动！'
            ],400);
        }
        $promotion = $this->handle->getBargainPromotion($list->promotion_id);
        $count = $this->handle->getBargainCount($promotion_id);
        if ($count>=$promotion->clickNum){
            return jsonResponse([
                'msg'=>'砍价已结束！'
            ],298);
        }

        $stock = $this->handle->getBargainStock($list->promotion_id,$list->stock_id);
        if (empty($stock)){
            return jsonResponse([
                'msg'=>'该库存不存在！'
            ],400);
        }
        $swapPrice = $this->handle->getBargainPromotionPrice($promotion_id);
        if($stock->origin_price-$swapPrice<=$stock->min_price){
            return jsonResponse([
                'msg'=>'砍价已结束！'
            ],299);
        }
        $price = $this->handle->getBargainPrice($promotion->clickNum-$count,$stock->origin_price-$stock->min_price-$swapPrice);
        if ($this->handle->addBargainRecord($user_id,$promotion_id,$price)){
            $this->handle->addBargainCount($promotion_id,$count+1);
            return jsonResponse([
                'msg'=>'ok',
                'data'=>[
                    'price'=>sprintf('%.2f',$stock->origin_price-$price),
                    'count'=>$count+1,
                    'bargain_price'=>sprintf('%.2f',$price)
                ]
            ]);
        };
    }
    public function getBargainRecords()
    {
        $promotion_id = Input::get('list_id');
        $page = Input::get('page',1);
        $limit = Input::get('limit',13);
        $data = $this->handle->getBargainRecords(0,$promotion_id,$page,$limit);
        $this->handle->formatBargainRecords($data['data']);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function addHotPromotion()
    {
        $id = Input::get('id');
        $promotion = $this->handle->getBargainPromotion($id);
        $hot = $promotion->hot == 1 ? 0 : 1;
        if ($hot){
            $count = $this->handle->countBargainPromotion(2,$id);
            if ($count>=6){
                return jsonResponse([
                    'msg'=>'超出数量限制！'
                ],400);
            }
        }
        $data = [
            'hot'=>$hot
        ];
        if ($this->handle->addBargainPromotion($id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function getMyPromotions()
    {
        $user_id = getRedisData(Input::get('token'));
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $data = $this->handle->getBargainRecords($user_id,0,$page,$limit);
        $this->handle->formatBargainRecords($data['data'],1);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function delBargainPromotion()
    {
        $id = Input::get('id');
        $this->handle->delBargainRecords($id);
        if ($this->handle->delBargainPromotion($id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function getBargainStock()
    {
        $promotion_id = Input::get('promotion_id');
        $product_id = Input::get('product_id');
        $details = Input::get('details');
        if ($details){
            $details = explode(',',$details);
            sort($details);
            $details = array_filter($details,function ($item){
                return $item!='';
            });
            $details = implode(',',$details);
        }
        $stock = $this->handle->getStock($product_id,$details);
        if (empty($stock)){
            return jsonResponse([
                'msg'=>'该规格没库存！'
            ],404);
        }
        $stock = $this->handle->getBargainStock($promotion_id,$stock->id);
        if (empty($stock)){
            return jsonResponse([
                'msg'=>'该规格没库存！'
            ],404);
        }
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$stock
        ]);
    }
    public function addBargainList()
    {
        $user_id = getRedisData(Input::get('token'));
        $promotion_id = Input::get('promotion_id');
        $stock_id = Input::get('stock_id');
        $promotion = $this->handle->getBargainPromotion($promotion_id);
        $list = $this->handle->getBargainList($user_id,$promotion_id);
        if (!empty($list)){
            return jsonResponse([
                'msg'=>'已开启拼团！'
            ],400);
        }
        $end = ($promotion->time*60*60)+time();
        if ($this->handle->addBargainList($promotion_id,$user_id,$stock_id,$end)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
}
