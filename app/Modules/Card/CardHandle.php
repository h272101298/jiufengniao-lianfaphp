<?php
/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/7/9
 * Time: 下午6:23
 */

namespace App\Modules\Card;


use App\Modules\Card\Model\CardList;
use App\Modules\Card\Model\CardPromotion;
use App\Modules\Card\Model\DefaultCard;
use App\Modules\Card\Model\UserCard;
use App\Modules\Product\Model\Product;
use App\Modules\Product\Model\ProductType;
use App\Modules\Product\Model\Stock;
use Illuminate\Support\Facades\DB;

trait CardHandle
{
    public function addCardPromotion($id,$data)
    {
        if ($id){
            $promotion = CardPromotion::find($id);
        }else{
            $promotion = new CardPromotion();
        }
        foreach ($data as $key=>$value){
            $promotion->$key = $value;
        }
        if ($promotion->save()){
            return $promotion->id;
        }
        return false;
    }
    public function getCardPromotionCount($state)
    {
        return CardPromotion::where('state','=',$state)->count();
    }
    public function addCardList($id,$data)
    {
        if ($id){
            $list = CardList::find($id);
        }else{
            $list = new CardList();
        }
        foreach ($data as $key => $value){
            $list->$key = $value;
        }
        if ($list->save()){
            return true;
        }
        return false;
    }
    public function getCardPromotions($product_id=[],$state=0,$page=1,$limit=10)
    {
        $db = DB::table('card_promotions');
        if ($product_id){
            $db->whereIn('product_id',$product_id);
        }
        if ($state){
            $db->where('state','=',$state);
        }
        $count = $db->count();
        $data = $db->limit($limit)->offset(($page-1)*$limit)->orderBy('id','DESC')->get();
        return [
            'data'=>$data,
            'count'=>$count
        ];
    }
    public function getCardPromotion($id)
    {
        return CardPromotion::find($id);
    }
    public function formatPromotions(&$promotions)
    {
        if (empty($promotions)){
            return [];
        }
        foreach ($promotions as $promotion){
            $stock = Stock::find($promotion->stock_id);
            $product = Product::find($stock->product_id);
            $type = ProductType::find($product->type_id);
            $promotion->product = $product;
            $promotion->type = $type;
            $promotion->start = date('Y-m-d H:i:s',$promotion->start);
            $promotion->end = date('Y-m-d H:i:s',$promotion->end);
            $promotion->clickCount = 0;
            $promotion->exchangeCount = 0;
            $promotion->list = CardList::where('promotion_id','=',$promotion->id)->get();
        }
        return $promotions;
    }
    public function formatPromotion(&$promotion)
    {
        $stock = Stock::find($promotion->stock_id);
        $product = Product::find($stock->product_id);
        $type = ProductType::find($product->type_id);
        $promotion->product = $product;
        $promotion->type = $type;
        $promotion->start = date('Y-m-d H:i:s',$promotion->start);
        $promotion->end = date('Y-m-d H:i:s',$promotion->end);
        $promotion->clickCount = 0;
        $promotion->exchangeCount = 0;
        $promotion->list = CardList::where('promotion_id','=',$promotion->id)->get();
        return $promotion;
    }
    public function getSeedCount($sum)
    {
        return 1/((0.2/0.0384)/$sum);
    }
    public function checkDraw($sum,$min)
    {
        $num = rand(1,$sum);
        if ($num<=$min){
            return true;
        }
        return false;
    }
    public function drawCard($cards)
    {
        return array_rand($cards,1);
    }
    public function getCardList($promotionId)
    {
        return CardList::where('promotion_id','=',$promotionId)->get();
    }
    public function addUserCard($user_id,$card_id)
    {
        $userCard = new UserCard();
        $userCard->user_id = $user_id;
        $userCard->card_id = $card_id;
        if ($userCard->save()){
            return true;
        }
        return false;
    }
    public function addDefaultCard($id,$data)
    {
        if ($id){
            $card = DefaultCard::find($id);
        }else{
            $card = new DefaultCard();
        }
        foreach ($data as $key => $value){
            $card->$key = $value;
        }
        if ($card->save()){
            return true;
        }
        return false;
    }
    public function countDefaultCard($id=0)
    {
        $db = DB::table('default_cards');
        if ($id){
            $db->where('id','!=',$id);
        }
        return $db->count();
    }
    public function delDefaultCards()
    {
        DefaultCard::truncate();
    }
    public function getDefaultCards()
    {
        $data =  DefaultCard::all();
        return $data;
    }
}