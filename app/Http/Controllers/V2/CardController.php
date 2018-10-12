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
        $limit = Input::get('limit',10);
        $data = $this->handle->getCardPromotions(null,$state,$page,$limit);
        $this->handle->formatPromotions($data['data']);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function enablePromotion()
    {
        $id = Input::get('id');
        $promotion = $this->handle->getCardPromotion($id);
        if ($promotion->state!=2) {
            return jsonResponse([
                'msg'=>'未审核的活动不能上线！'
            ],400);
        }
        $data = [
            'enable'=>$promotion->enable==0?1:0
        ];
        if ($this->handle->addCardPromotion($id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
    }
    public function getEnablePromotions()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $data = $this->handle->getCardPromotions(null,0,$page,$limit,2);
        $this->handle->formatCardPromotions($data['data']);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function getEnablePromotion()
    {
        $id = Input::get('id');
        $user_id = getRedisData(Input::get('token'));
        $founder_id = Input::get('founder_id');
        $founder_id = $founder_id==0?$user_id:$founder_id;
        $data = $this->handle->getCardPromotion($id);
        $this->handle->formatCardPromotion($data,$user_id,$founder_id);
        $data->express = $this->handle->getStoreExpress($data->store_id);
        $data->store = $this->handle->getStoreById($data->store_id);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function checkPromotion()
    {
        $id = Input::get('id');
        $data = ['state'=>Input::get('state',3)];
        if ($this->handle->addCardPromotion($id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ]);
    }
    public function delCardPromotion()
    {
        $id = Input::get('id');
        $this->handle->delPromotionCardList($id);
        $this->handle->delCardPromotion($id);
        $this->handle->delHotCardPromotion($id);
        return jsonResponse([
            'msg'=>'ok'
        ]);
    }
    public function modifyCardPromotion(Request $post)
    {
        $id = $post->id;
        $data = [
            'description'=>$post->description,
            'start'=>strtotime($post->start),
            'end'=>strtotime($post->end),
            'number'=>$post->number,
            'offer'=>$post->offer,
            'clickNum'=>$post->clickNum,
        ];
        $this->handle->addCardPromotion($id,$data);
        $list = $post->list;
        if (!empty($list)){
            $this->handle->delPromotionCardList($id);
            foreach ($list as $item){
                $swap = [
                    'cover'=>$item
                ];
                $this->handle->addCardList(0,$swap);
            }
        }
        return jsonResponse([
            'msg'=>'ok'
        ]);
    }

    public function getCardPromotion()
    {
        $id = Input::get('id');
        $user_id = getRedisData(Input::get('token'))?getRedisData(Input::get('token')):0;
        $data = $this->handle->getCardPromotion($id);
        $this->handle->formatPromotion($data,$user_id,$user_id);
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
    public function drawCard()
    {
        $id = Input::get('promotion_id');
        $user_id = getRedisData(Input::get('token'));
        $founder_id = Input::get('founder_id');
        $founder_id = $founder_id==0?$user_id:$founder_id;
        if ($this->handle->checkCardJoin($id,$user_id,$founder_id)){
            return jsonResponse([
                'msg'=>'已参加过的活动不能再添加！'
            ],400);
        }
        $promotion = $this->handle->getCardPromotion($id);
        $count = $this->handle->getUserCardCount($founder_id,$id);
        $cards = $this->handle->getCardListArray($id);
        $card = 0;
        switch ($count){
            case 0:
                $card = $this->handle->drawCard($cards);
                $this->handle->addUserCard($founder_id,$card,$id);
                break;
            case 1:
                if ($this->handle->checkDraw(10,8)){
                    $card = $this->handle->drawCard($cards);
                    $this->handle->addUserCard($founder_id,$card,$id);
                };
                break;
            case 2:
                if ($this->handle->checkDraw(10,6)){
                    $card = $this->handle->drawCard($cards);
                    $this->handle->addUserCard($founder_id,$card,$id);
                };
                break;
            case 3:
                if ($this->handle->checkDraw(10,4)){
                    $card = $this->handle->drawCard($cards);
                    $this->handle->addUserCard($founder_id,$card,$id);
                };
                break;
            case 4:
                $sum = $this->handle->getSeedCount($promotion->clickNum);
                if ($sum<=1){
                    $card = $this->handle->drawCard($cards);
                    $this->handle->addUserCard($founder_id,$card,$id);
                    break;
                }else{
                    if ($this->handle->checkDraw($sum,1)){
                        $card = $this->handle->drawCard($cards);
                        $this->handle->addUserCard($founder_id,$card,$id);
                    };
                }
                break;
            default:
                $sum = $this->handle->getSeedCount($promotion->clickNum);
                if ($sum<=1){
                    $card = $this->handle->drawCard($cards);
                    $this->handle->addUserCard($founder_id,$card,$id);
                }else{
                    if ($this->handle->checkDraw($sum,1)){
                        $card = $this->handle->drawCard($cards);
                        $this->handle->addUserCard($founder_id,$card,$id);
                    };
                }
                break;
        }
        if ($user_id!=$founder_id){
            $this->handle->addCardJoinRecord($user_id,$card,$founder_id,$id);
        }
        if ($card!=0){
            $cardCount = $this->handle->getUserCardCount($founder_id,$id);
            if ($cardCount==5){
                if (!$this->handle->checkCardPromotionNotify($id)){
                    $user = $this->handle->getWeChatUserById($user_id);
                    $list = $this->handle->getNotifyListByOpenId($user->open_id);
                    $product = $this->handle->getProductById($promotion->product_id);
                    if (!empty($list)){
                        $data = [
                            "touser"=>$list->open_id,
                            "template_id"=>$this->handle->getNotifyConfigByTitle('card_notify'),
                            "form_id"=>$list->notify_id,
                            "page"=>"pages/activity/card/detail/detail?id=0&cardid=".$promotion->id,
                            "data"=>[
                                "keyword1"=>[
                                    "value"=>$promotion->description
                                ],
                                "keyword2"=>[
                                    "value"=>date('Y-m-d H:i:s',time())
                                ],
                                "keyword3"=>[
                                    "value"=>$product->name
                                ]
                            ]
                        ];
                        $this->handle->addNotifyQueue(json_encode($data));
                        $this->handle->addCardPromotionNotify($id);
                        $this->handle->delNotifyList($list->id);
                    }

                }

            }
        }
        $this->handle->addCardJoin($id,$user_id,$founder_id);
        return jsonResponse(['msg'=>'ok', 'data'=>$card]);
    }
    public function getCardJoinRecords()
    {
        $user_id = getRedisData(Input::get('token'));
        $promotion_id = Input::get('promotion_id');
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $data = $this->handle->getCardJoinRecords($user_id,$promotion_id,$page,$limit);
        $this->handle->formatCardJoinRecords($data['data']);
        return jsonResponse([
            'data'=>$data,
            'msg'=>'ok'
        ]);
    }
    public function giftCard()
    {
        $user_id = getRedisData(Input::get('token'));
        $card_id = Input::get('card_id');
        $presenter = Input::get('presenter');
        $cards = $this->handle->getUserCard($presenter,$card_id);
        if (count($cards)<=1){
            return jsonResponse([
                'msg'=>'该卡片已被领取!'
            ],400);
        }
        $card = array_random($cards,1);
        $card->user_id = $user_id;
        if ($card->save()){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'领取失败！'
        ],400);
    }
    public function addHotCardPromotion()
    {
        $id = Input::get('id');
        $count = $this->handle->countHotCardPromotions($id);
        if ($count>=4){
            return jsonResponse([
                'msg'=>'最多4个推荐活动！'
            ],400);
        }
        if ($this->handle->addHotCardPromotion($id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function getHotCardPromotions()
    {
        $hot = $this->handle->getHotCardPromotions();
        if (empty($hot)){
            $data = [];
        }else{
            $data = $this->handle->getCardPromotions(null,0,1,10,0,$hot);
            $this->handle->formatCardPromotions($data['data']);
        }
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
}
