<?php
/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/8/2
 * Time: 上午11:37
 */

namespace App\Modules\GroupBuy;


use App\Modules\GroupBuy\Model\GroupBuyJoin;
use App\Modules\GroupBuy\Model\GroupBuyList;
use App\Modules\GroupBuy\Model\GroupBuyPromotion;
use App\Modules\GroupBuy\Model\GroupBuyStock;
use App\Modules\Product\Model\Product;
use App\Modules\Product\Model\Stock;
use App\Modules\Product\Model\StockImage;
use App\Modules\Store\Model\Store;
use App\Modules\WeChatUser\Model\WeChatUser;
use Illuminate\Support\Facades\DB;

trait GroupBuyHandle
{
    public function addGroupBuyPromotion($id,$data)
    {
        if ($id){
            $promotion = GroupBuyPromotion::find($id);
        }else {
            $promotion = new GroupBuyPromotion();
        }
        foreach ($data as $key => $value){
            $promotion -> $key = $value;
        }
        if ($promotion->save()){
            return $promotion->id;
        }
        return false;
    }
    public function getGroupBuyPromotions($store_id=0,$product_id=null,$stock_id=null,$title='',$hot=0,$state=0,$enable=0,$page=1,$limit=10,$free=0)
    {
        $db = DB::table('group_buy_promotions');
        if ($store_id){
            $db->where('store_id','!=',$store_id);
        }
        if (!empty($product_id)){
            $db->whereIn('product_id',$product_id);
        }
        if ($stock_id){
            $db->whereIn('stock_id',$stock_id);
        }
        if ($title){
            $db->where('title','like','%'.$title.'%');
        }
        if ($hot){
            $db->where('hot','=',$hot-1);
        }
        if ($state){
            $db->where('state','=',$state);
        }
        if ($enable){
            $db->where('enable','=',$enable-1);
        }
        if ($free){
            $db->where('free','=',$free-1);
        }
        $count = $db->count();
        $data = $db->orderBy('id','DESC')->limit($limit)->offset(($page-1)*$limit)->get();
        return [
            'data'=>$data,
            'count'=>$count
        ];
    }
    public function formatGroupBuyPromotions(&$promotions,$store=0,$product=0,$stock=0)
    {
        if (empty($promotions)){
            return [];
        }
        foreach ($promotions as $promotion){
            //$promotion->start = date('Y-m-d H:i:s',$promotion->start);
            //$promotion->end = date('Y-m-d H:i:s',$promotion->end);
            if ($store){
                $store = Store::find($promotion->store_id);
                $promotion->store = $store;
            }
            if ($product){
                $product = Product::find($promotion->product_id);
                if (!empty($product)){
                    unset($product->detail);
                }
                $promotion->product = $product;
            }
            if ($stock){
                $stocks = GroupBuyStock::where('group_id','=',$promotion->id)->orderBy('group_price','ASC')->get();
                foreach ($stocks as $stock){
                    $stock->stock = Stock::find($stock->stock_id);
                    $stock->images = StockImage::where('stock_id','=',$stock->stock_id)->get();
                }
                $promotion->stocks = $stocks;
            }
        }
        return $promotions;
    }
    public function getGroupBuyPromotion($id)
    {
        return GroupBuyPromotion::findOrFail($id);
    }
    public function delGroupBuyPromotion($id)
    {
        $promotion = GroupBuyPromotion::findOrFail($id);
        if ($promotion->delete()){
            GroupBuyStock::where('group_id','=',$id)->delete();
            return true;
        }
        return false;
    }
    public function formatGroupBuyPromotion(&$promotion,$store=0,$product=0,$stock=0)
    {
        if (empty($promotion)){
            return null;
        }
        //$promotion->start = date('Y-m-d H:i:s',$promotion->start);
        //$promotion->end = date('Y-m-d H:i:s',$promotion->end);
        if ($store){
            $store = Store::find($promotion->store_id);
            $promotion->store = $store;
        }
        if ($product){
            $product = Product::find($promotion->product_id);
            $promotion->product = $product;
        }
        if ($stock){
            $stocks = GroupBuyStock::where('group_id','=',$promotion->id)->orderBy('group_price','ASC')->get();
            foreach ($stocks as $stock){
                $stock->stock = Stock::find($stock->stock_id);
                $stock->images = StockImage::where('stock_id','=',$stock->stock_id)->get();
            }
            $promotion->stocks = $stocks;
        }
    }
    public function countGroupBuyPromotion($hot=0,$id)
    {
        $db = DB::table('group_buy_promotions');
        if ($id){
            $db->where('id','!=',$id);
        }
        if ($hot){
            $db->where('hot','=',$hot-1);
        }
        return $db->count();
    }
    public function addGroupBuyStock($id,$data)
    {
        if ($id){
            $stock = GroupBuyStock::find($id);
        }else{
            $stock = new GroupBuyStock();
        }
        foreach ($data as $key => $value){
            $stock->$key = $value;
        }
        if ($stock->save()){
            return true;
        }
        return false;
    }
    public function getGroupBuyStock($group_id,$stock_id)
    {
        return GroupBuyStock::where('group_id','=',$group_id)->where('stock_id','=',$stock_id)->first();
    }
    public function addGroupBuyJoin($id,$data)
    {
        if ($id){
            $join = GroupBuyJoin::find($id);
        }else{
            $join = new GroupBuyJoin();
        }
        foreach ($data as $key => $value){
            $join->$key = $value;
        }
        if ($join->save()){
            return true;
        }
        return false;
    }
    public function addGroupBuyList($id,$data)
    {
        if ($id){
            $list = GroupBuyList::find($id);
        }else{
            $list = new GroupBuyList();
        }
        foreach ($data as $key => $value){
            $list->$key = $value;
        }
        if ($list->save()){
            return $list->id;
        }
        return false;
    }
    public function getGroupBuyList($id)
    {
        return GroupBuyList::find($id);
    }
    public function formatGroupBuyList($list,$product=1,$stock=1)
    {
        $promotion = $this->getGroupBuyPromotion($list->group_id);
        if ($product){
            $product = Product::find($promotion->product_id);
            $promotion->product = $product;
        }
        if ($stock){
            $stocks = GroupBuyStock::where('group_id','=',$promotion->id)->orderBy('group_price','ASC')->get();
            foreach ($stocks as $stock){
                $stock->stock = Stock::find($stock->stock_id);
                $stock->images = StockImage::where('stock_id','=',$stock->stock_id)->get();
            }
            $promotion->stocks = $stocks;
        }
        $joins = GroupBuyJoin::where('list_id','=',$list->id)->where('state','=',1)->get();
        if (!empty($joins)){
            foreach ($joins as $join){
                $join->user = WeChatUser::find($join->user_id);
            }
        }
        $list->promotion = $promotion;
        $list->joins = $joins;
        return $list;
    }
    public function getGroupBuyLists($group_id)
    {
        $db = DB::table('group_buy_lists')->where('group_id','=',$group_id)->where('state','=',1)->where('end','>',time());
        return [
            'count'=>$db->count(),
            'data'=>$db->get()
        ];
    }
    public function formatGroupBuyLists(&$lists)
    {
        if (empty($lists)){
            return [];
        }
        foreach ($lists as $list){
            $group = $this->getGroupBuyPromotion($list->group_id);
            $list->user = WeChatUser::find($list->user_id);
            $list->need = $group->people_number - $this->getGroupBuyJoinNumber($list->id);
        }
        return $lists;
    }
    public function getGroupBuyJoinNumber($list_id)
    {
        return GroupBuyJoin::where('list_id','=',$list_id)->where('state','=',1)->count();
    }
}