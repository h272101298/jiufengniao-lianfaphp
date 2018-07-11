<?php

namespace App\Http\Controllers\V2;

use App\Modules\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class CardController extends Controller
{
    //
    private $handle;
    public function __construct()
    {
        $this->handle = new User();
    }

    public function addCardPromotion(Request $post)
    {
        $stock = $this->handle->getStockById($post->stock_id);
        $product = $this->handle->getProductById($stock->product_id);
        $data = [
            'stock_id'=>$stock->id,
            'product_id'=>$product->id,
            'store_id'=>$product->store_id,
            'description'=>$post->description,
            'start'=>strtotime($post->start),
            'end'=>strtotime($post->end),
            'number'=>$post->number,
            'offer'=>$post->offer,
            'clickNum'=>$post->clickNum
        ];

        $promotionId = $this->handle->addCardPromotion(0,$data);
        $default = $post->default;
        if ($default){
            $list = $this->handle->getDefaultCards();
            foreach ($list as $item){
                $listData = [
                    'promotion_id'=>$promotionId,
                    'cover'=>$item->cover
                ];
                $this->handle->addCardList(0,$listData);
            }
        }else{
            $list = $post->list;
            foreach ($list as $item){
                $listData = [
                    'promotion_id'=>$promotionId,
                    'cover'=>$item
                ];
                $this->handle->addCardList(0,$listData);
            }
        }
        return jsonResponse([
            'msg'=>'ok'
        ]);
    }
    public function getCardPromotions()
    {
        $state = Input::get('state');
        $page = Input::get('page',1);
        $limit = Input::get('limit');
        $data = $this->handle->getCardPromotions(null,$state,$page,$limit);
        $this->handle->formatPromotions($data['data']);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function checkPromotion()
    {
        $id = Input::get('id');
        $data = ['state'=>2];
        if ($this->handle->addCardPromotion($id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ]);
    }
    public function modifyPromotion()
    {
        
    }
    public function getCardPromotion()
    {
        $id = Input::get('id');
        $data = $this->handle->getCardPromotion($id);
        $this->handle->formatPromotion($data);
        return jsonResponse([
            'data'=>$data,
            'msg'=>'ok'
        ]);
    }

    public function getCardList()
    {

    }
    public function addDefaultCard(Request $post)
    {
        $list = $post->list;
        $this->handle->delDefaultCards();
        foreach ($list as $item){
            $data = [
                'cover'=>$item
            ];
            $this->handle->addDefaultCard(0,$data);
        }
        return jsonResponse([
            'msg'=>'ok'
        ]);
    }
    public function getDefaultCards()
    {
        $data = $this->handle->getDefaultCards();
        return jsonResponse([
            'data'=>$data,
            'msg'=>'ok'
        ]);
    }
}
