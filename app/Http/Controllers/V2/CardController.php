<?php

namespace App\Http\Controllers\V2;

use App\Modules\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

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
        $data = [
            'stock_id'=>$post->stock_id,
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
    public function getCardPromotion()
    {

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
