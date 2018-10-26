<?php
/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/7/18
 * Time: 下午12:00
 */

namespace App\Modules\Bargain;


use App\Modules\Bargain\Model\BargainList;
use App\Modules\Bargain\Model\BargainPromotion;
use App\Modules\Bargain\Model\BargainRecord;
use App\Modules\Bargain\Model\BargainStock;
use App\Modules\Product\Model\Product;
use App\Modules\Product\Model\Stock;
use App\Modules\Store\Model\StoreExpress;
use App\Modules\WeChatUser\Model\WeChatUser;
use function GuzzleHttp\Psr7\uri_for;
use Illuminate\Support\Facades\DB;

trait BargainHandle
{
    public function addBargainPromotion($id,$data)
    {
        if ($id){
            $promotion = BargainPromotion::find($id);
        }else{
            $promotion = new BargainPromotion();
        }
        foreach ($data as $key => $value){
            $promotion -> $key = $value;
        }
        if ($promotion->save()){
            return $promotion->id;
        }
        return false;
    }
    public function getBargainPromotions($product_id=[],$store_id=0,$state=0,$page=1,$limit=10,$enable=0,$hot=0)
    {
        $DB = DB::table('bargain_promotions');
        if (!empty($product_id)){
            $DB->whereIn('product_id',$product_id);
        }
        if ($store_id){
            $DB->where('store_id','=',$store_id);
        }
        if ($state){
            $DB->where('state','=',$state);
        }
        if ($enable){
            $DB->where('enable','=',$enable-1);
        }
        if ($hot){
            $DB->where('hot','=',$hot-1);
        }
        $count = $DB->count();
        $data = $DB->orderBy('id','DESC')->limit($limit)->offset(($page-1)*$limit)->get();
//        dd($data);
        return [
            'count'=>$count,
            'data'=>$data
        ];
    }
    public function formatBargainPromotions(&$promotions,$formatStock=0,$user_id=0)
    {
        if (empty($promotions)){
            return [];
        }
        foreach ($promotions as $promotion){
            $product = Product::find($promotion->product_id);
            if (!empty($product)){
                unset($product->detail);
            }
            $promotion->product = $product;
            $stocks = BargainStock::where('bargain_id','=',$promotion->id)->orderBy('origin_price','ASC')->get();
            if (!empty($stocks)){
                foreach ($stocks as $stock){
                    $stock->origin_price = number_format($stock->origin_price,2);
                    $stock->min_price = number_format($stock->min_price,2);
                    if ($formatStock){
                        $stock->info = Stock::find($stock->stock_id);
                    }
                }
            }
            $promotion->stocks = $stocks;
            $promotion->bargain_count = $this->getBargainCount($promotion->id);
            $promotion->bargain_price = $this->getBargainPromotionPrice($promotion->id);
            $promotion->count = BargainList::where('promotion_id','=',$promotion->id)->count();
            if ($user_id){
                $promotion->join = BargainList::where('promotion_id','=',$promotion->id)->where('user_id','=',$user_id)->first();
            }
        }
        return $promotions;
    }

    public function getBargainPromotion($id)
    {
        return BargainPromotion::find($id);
    }
    public function formatBargainPromotion(&$promotion,$time=0,$bargain=0,$join=0,$express=0)
    {
        $promotion->product = Product::find($promotion->product_id);
        $stocks = BargainStock::where('bargain_id','=',$promotion->id)->get();
        if (!empty($stocks)){
            foreach ($stocks as $stock){
                $stock->origin_price = number_format($stock->origin_price,2);
                $stock->min_price = number_format($stock->min_price,2);
                $stock->info = Stock::find($stock->stock_id);
            }
        }
        if ($join){
            $promotion->join = BargainList::where('promotion_id','=',$promotion->id)->where('user_id','=',$join)->first();
        }
        if ($express){
            $promotion->express = StoreExpress::where('store_id','=',$promotion->store_id)->first();
        }
        $promotion->stocks = $stocks;
        $promotion->bargain = $bargain;
    }
    public function addBargainRecord($user_id,$promotion_id,$price)
    {
        $record = new BargainRecord();
        $record->user_id = $user_id;
        $record->promotion_id = $promotion_id;
        $record->price = $price;
        if ($record->save()){
            return true;
        }
        return false;
    }
    public function checkUserBargain($user_id,$promotion_id)
    {
        $count = BargainRecord::where('user_id','=',$user_id)->where('promotion_id','=',$promotion_id)->count();
        if ($count!=0){
            return true;
        }
        return false;
    }
    public function getBargainPromotionPrice($promotion)
    {
        $price = BargainRecord::where('promotion_id','=',$promotion)->sum('price');
        return $price;
    }
    public function getBargainPrice($count,$price)
    {
        if ($count==1){
            return sprintf('%.2f',$price);
        }
        $swap = ($price/$count)*2;
        $result =  0.01 + mt_rand() / mt_getrandmax() * ($swap - 0.01);
        return sprintf('%.2f',$result);
    }
    public function addBargainCount($id,$count)
    {
        $key = 'bargain'.$id;
        setRedisData($key,$count);
    }
    public function getBargainCount($id)
    {
        return getRedisData('bargain'.$id);
    }
    public function getBargainRecords($user_id,$promotion_id,$page=1,$limit=10)
    {
        $DB = DB::table('bargain_records');
        if ($user_id){
            $DB->where('user_id','=',$user_id);
        }
        if ($promotion_id){
            $DB->where('promotion_id','=',$promotion_id);
        }
        $count = $DB->count();
        $data = $DB->orderBy('id','DESC')->limit($limit)->offset(($page-1)*$limit)->get();
        return [
            'data'=>$data,
            'count'=>$count
        ];
    }
    public function formatBargainRecords(&$records,$productInfo=0)
    {
        if (empty($records)){
            return [];
        }
        foreach ($records as $record){
            $record->user = WeChatUser::find($record->user_id);
            $price = BargainRecord::where('promotion_id','=',$record->id)->sum('price');
            $record->price = number_format($price,2);
            if ($productInfo){
                //$list = $this->getBargainListById($record->promotion_id);
                $promotion = $this->getBargainPromotion($record->promotion_id);
                if (!empty($promotion)){
                    $record->promotion = $promotion;
                    $record->stock = Stock::find($record->stock_id);
                    $record->product = Stock::find($promotion->product_id);
                }else{
                    $record->promotion = null;
                }
            }
        }
        return $records;
    }
    public function countBargainPromotion($hot=0,$id)
    {
        $db = DB::table('bargain_promotions');
        if ($id){
            $db->where('id','!=',$id);
        }
        if ($hot){
            $db->where('hot','=',$hot-1);
        }
        return $db->count();
    }
    public function delBargainRecords($promotion_id)
    {
        return BargainRecord::where('promotion_id','=',$promotion_id)->delete();
    }
    public function delBargainPromotion($id)
    {
        $promotion = BargainPromotion::find($id);
        if ($promotion->delete()){
            return true;
        }
        return false;
    }
    public function addBargainStock($id,$data)
    {
        if ($id){
            $stock = BargainStock::find($id);
        }else{
            $stock = new BargainStock();
        }
        foreach ($data as $key => $value){
            $stock->$key = $value;
        }
        if ($stock->save()){
            return true;
        }
        return false;
    }
    public function getBargainStocks($bargain_id)
    {
        $stocks = BargainStock::where('bargain_id','=',$bargain_id)->orderBy('origin_price','ASC')->get();
        if (!empty($stocks)){
            foreach ($stocks as $stock){
                $stock->info = Stock::find($stock->stock_id);
            }
        }
        return $stocks;
    }
    public function getBargainStock($bargain_id,$stock_id)
    {
        $stock =  BargainStock::where('bargain_id','=',$bargain_id)->where('stock_id','=',$stock_id)->first();
        if (empty($stock)){
            return null;
        }
        $stock->info = Stock::find($stock->stock_id);
        return $stock;
    }
    public function delBargainStock($id)
    {
        $stock = BargainStock::find($id);
        if ($stock->delete()){
            return true;
        }
        return false;
    }
    public function delBargainStocks($bargain_id)
    {
        return BargainStock::where('bargain_id','=',$bargain_id)->delete();
    }
    public function addBargainList($bargain_id,$user_id,$stock_id,$end)
    {
        $list = new BargainList();
        $list->promotion_id = $bargain_id;
        $list->user_id = $user_id;
        $list->stock_id = $stock_id;
        $list->end = $end;
        if ($list->save()){
            return true;
        }
        return false;
    }
    public function getBargainList($user_id,$promotion_id)
    {
        return BargainList::where('promotion_id','=',$promotion_id)->where('user_id','=',$user_id)->first();
    }
    public function getBargainLists($user_id,$page=1,$limit=10)
    {
        $db = DB::table('bargain_lists');
        $db->where('user_id','=',$user_id);
        $count = $db->count();
        $data = $db->orderBy('id','DESC')->limit($limit)->offset(($page-1)*$limit)->get();
        return [
            'count'=>$count,
            'data'=>$data
        ];
    }
    public function getBargainListById($id)
    {
        return BargainList::findOrFail($id);
    }
}