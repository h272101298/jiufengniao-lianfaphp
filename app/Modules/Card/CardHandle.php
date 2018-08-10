<?php
/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/7/9
 * Time: 下午6:23
 */

namespace App\Modules\Card;


use App\Modules\Card\Model\CardJoinList;
use App\Modules\Card\Model\CardJoinRecord;
use App\Modules\Card\Model\CardList;
use App\Modules\Card\Model\CardNotify;
use App\Modules\Card\Model\CardPrizeList;
use App\Modules\Card\Model\CardPromotion;
use App\Modules\Card\Model\DefaultCard;
use App\Modules\Card\Model\HotCardPromotion;
use App\Modules\Card\Model\UserCard;
use App\Modules\Product\Model\Product;
use App\Modules\Product\Model\ProductType;
use App\Modules\Product\Model\Stock;
use App\Modules\WeChatUser\Model\WeChatUser;
use Illuminate\Support\Facades\DB;

trait CardHandle
{
    /**
     * @param $id
     * @param $data
     * @return bool|mixed
     * 新增集卡牌活动
     */
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

    /**
     * @param $state
     * @return mixed
     * 获取集卡牌活动列表
     */
    public function getCardPromotionCount($state)
    {
        return CardPromotion::where('state','=',$state)->count();
    }

    /**
     * @param $id
     * @return bool
     * 删除集卡牌活动
     */
    public function delCardPromotion($id)
    {
        $promotion = CardPromotion::find($id);
        if ($promotion->delete()){
            return true;
        }
        return false;
    }

    /**
     * @param $promotionId
     * @return mixed
     * 删除卡牌列表
     */
    public function delPromotionCardList($promotionId)
    {
        return CardList::where('promotion_id','=',$promotionId)->delete();
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
    public function getCardPromotions($product_id=[],$state=0,$page=1,$limit=10,$enable=0,$idArray=[])
    {
        $db = DB::table('card_promotions');
        if (!empty($product_id)){
            $db->whereIn('product_id',$product_id);
        }
        if ($state){
            $db->where('state','=',$state);
        }
        if ($enable){
            $db->where('enable','=',$enable-1);
        }
        if (!empty($idArray)){
            $db->whereIn('id',$idArray);
        }
        $count = $db->count();
        $data = $db->limit($limit)->offset(($page-1)*$limit)->orderBy('id','DESC')->get();
        return [
            'data'=>$data,
            'count'=>$count
        ];
    }
    public function formatCardPromotions(&$promotions)
    {
        if (empty($promotions)){
            return [];
        }
        foreach ($promotions as $promotion) {
//            dd($promotion);
            $promotion->stock = Stock::find($promotion->stock_id);
            $product = Product::find($promotion->product_id);
            if (!empty($product)){
                unset($product->detail);
            }
            $promotion->product = $product;
        }
        return $promotions;
    }
    public function formatCardPromotion(&$promotion,$user_id=0,$founder_id=0)
    {
        $promotion->stock = Stock::find($promotion->stock_id);
        $list = CardList::where('promotion_id','=',$promotion->id)->get();
        foreach ($list as $item){
            $item->count = UserCard::where('user_id','=',$founder_id)
                ->where('promotion_id','=',$promotion->id)->where('card_id','=',$item->id)->count();
        }
        $promotion->list = $list;
        $promotion->product = Product::find($promotion->product_id);
        $stock = Stock::find($promotion->stock_id);
        $promotion->join = CardJoinList::where('promotion_id','=',$promotion->id)
            ->where('user_id','=',$user_id)->where('founder_id','=',$founder_id)->count();
        $promotion->prize = CardPrizeList::where('user_id','=',$founder_id)->where('promotion_id','=',$promotion->id)->count();
        $promotion->price = sprintf('%.2f',$stock->price*($promotion->offer/10));
        return $promotion;
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
            $product = empty($stock)?null:Product::find($stock->product_id);
            $type = empty($product)?null:ProductType::find($product->type_id);
            if (!empty($product)){
                unset($product->detail);
            }
            $promotion->hot = $this->checkHotCardPromotion($promotion->id);
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
        return $promotion;
    }
    public function getSeedCount($sum)
    {
        return intval(1/((0.2/0.0384)/$sum));
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
        return array_random($cards);
    }
    public function getUserCard($user_id=0,$card_id=0)
    {
        $DB = DB::table('user_cards');
        if ($user_id){
            $DB->where('user_id','=',$user_id);
        }
        if ($card_id){
            $DB->where('card_id','=',$card_id);
        }
        return $DB->get();
    }
    public function getUserCardCount($user_id=0,$promotion_id = 0,$card_id=0)
    {
        $DB = DB::table('user_cards');
        if ($user_id){
            $DB->where('user_id','=',$user_id);
        }
        if ($promotion_id){
            $DB->where('promotion_id','=',$promotion_id);
        }
        if ($card_id){
            $DB->where('card_id','=',$card_id);
        }
        return count($DB->groupBy('card_id')->get());
    }
    public function getCardList($promotionId)
    {
        return CardList::where('promotion_id','=',$promotionId)->get();
    }
    public function getCardListArray($promotionId)
    {
        return CardList::where('promotion_id','=',$promotionId)->pluck('id')->toArray();
    }
    public function addUserCard($user_id,$card_id,$promotion_id)
    {
        $userCard = new UserCard();
        $userCard->user_id = $user_id;
        $userCard->card_id = $card_id;
        $userCard->promotion_id = $promotion_id;
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
    public function checkCardJoin($promotion_id,$user_id,$founder_id)
    {
        $count = CardJoinList::where('promotion_id','=',$promotion_id)
            ->where('user_id','=',$user_id)->where('founder_id','=',$founder_id)->count();
        if ($count!=0){
            return true;
        }
        return false;
    }
    public function addCardJoin($promotion_id,$user_id,$founder_id)
    {
        $join = new CardJoinList();
        $join->user_id = $user_id;
        $join->promotion_id = $promotion_id;
        $join->founder_id = $founder_id;
        if ($join->save()){
            return true;
        }
        return false;
    }
    public function addCardJoinRecord($user_id,$card_id,$founder_id,$promotion_id)
    {
        $record = new CardJoinRecord();
        $record->user_id = $user_id;
        $record->card_id = $card_id;
        $record->founder_id = $founder_id;
        $record->promotion_id = $promotion_id;
        if ($record->save()){
            return true;
        }
        return false;
    }
    public function getCardJoinRecords($founder_id,$promotion_id,$page=1,$limit=10)
    {
        $db = DB::table('card_join_records')->where('founder_id','=',$founder_id)->where('promotion_id','=',$promotion_id);
        $count = $db->count();
        $data = $db->orderBy('id','DESC')->limit($limit)->offset(($page-1)*$limit)->get();
        return [
            'data'=>$data,
            'count'=>$count
        ];
    }
    public function formatCardJoinRecords(&$records)
    {
        if (empty($records)){
            return [];
        }
        foreach ($records as $record){
            $record->user = WeChatUser::find($record->user_id);
        }
        return $records;
    }
    public function addCardPrize($user_id,$promotion_id)
    {
        $prize = new CardPrizeList();
        $prize->user_id = $user_id;
        $prize->promotion_id = $promotion_id;
        if ($prize->save()){
            return true;
        }
        return false;
    }
    public function countCardPrize($user_id,$promotion_id)
    {
        return CardPrizeList::where('user_id','=',$user_id)->where('promotion_id','=',$promotion_id)->count();
    }
    public function getUserJoinPromotions($user_id,$page=1,$limit=10)
    {
        $db = DB::table('card_join_lists')->where('user_id','=',$user_id)->where('founder_id','=',$user_id);
        $count = $db->count();
        $promotionsId = $db->limit($limit)->offset(($page-1)*$limit)->pluck('promotion_id')->toArray();
        $promotions = CardPromotion::whereIn('id',$promotionsId)->get();
        return [
            'data'=>$promotions,
            'count'=>$count
        ];
    }
    public function formatUserJoinPromotions(&$promotions,$user_id)
    {
        if (empty($promotions)){
            return [];
        }
        foreach ($promotions as $promotion){
            $promotion->stock = Stock::find($promotion->stock_id);
            $promotion->product = Product::find($promotion->product_id);
            $promotion->count = $this->getUserCardCount($user_id,$promotion->id);
        }
    }
    public function checkCardPromotionNotify($promotion)
    {
        $count = CardNotify::where('promotion_id','=',$promotion)->count();
        if ($count!=0){
            return true;
        }
        return false;
    }
    public function addCardPromotionNotify($promotion)
    {
        $notify = new CardNotify();
        $notify->promotion_id = $promotion;
        if ($notify->save()){
            return true;
        }
        return false;
    }
    public function addHotCardPromotion($id)
    {
        $hot = HotCardPromotion::where('promotion_id','=',$id)->first();
        if (empty($hot)){
            $hot = new HotCardPromotion();
            $hot->promotion_id = $id;
            if ($hot->save()){
                return true;
            }
            return false;
        }
        if ($hot->delete()){
            return true;
        }
        return false;
    }
    public function checkHotCardPromotion($id)
    {
        return HotCardPromotion::where('promotion_id','=',$id)->count();
    }
    public function getHotCardPromotions()
    {
        return HotCardPromotion::pluck('promotion_id')->toArray();
    }
    public function countHotCardPromotions($id)
    {
        return HotCardPromotion::where('promotion_id','!=',$id)->count();
    }
    public function delHotCardPromotion($id)
    {
        return HotCardPromotion::where('promotion_id','=',$id)->delete();
    }

}