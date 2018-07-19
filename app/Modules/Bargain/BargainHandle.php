<?php
/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/7/18
 * Time: 下午12:00
 */

namespace App\Modules\Bargain;


use App\Modules\Bargain\Model\BargainPromotion;
use App\Modules\Bargain\Model\BargainRecord;
use App\Modules\Product\Model\Product;
use App\Modules\Product\Model\Stock;
use App\Modules\WeChatUser\Model\WeChatUser;
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
        return [
            'count'=>$count,
            'data'=>$data
        ];
    }
    public function formatBargainPromotions(&$promotions,$time=0)
    {
        if (empty($promotions)){
            return [];
        }
        foreach ($promotions as $promotion){
            $promotion->product = Product::find($promotion->product_id);
            $promotion->stock = Stock::find($promotion->stock_id);
            if ($time){
                $promotion->start = date('Y-m-d H:i:s',$promotion->start);
                $promotion->end = date('Y-m-d H:i:s',$promotion->end);
            }
            $promotion->bargain_count = $this->getBargainCount($promotion->id);
            $promotion->bargain_price = $this->getBargainPromotionPrice($promotion->id);
        }
    }

    public function getBargainPromotion($id)
    {
        return BargainPromotion::findOrFail($id);
    }
    public function formatBargainPromotion(&$promotion,$time=0,$bargain=0)
    {
        $promotion->product = Product::find($promotion->product_id);
        $promotion->stock = Stock::find($promotion->stock_id);
        $promotion->bargain = $bargain;
        if ($time){
            $promotion->start = date('Y-m-d H:i:s',$promotion->start);
            $promotion->end = date('Y-m-d H:i:s',$promotion->end);
        }
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
            return $price;
        }
        return rand(0.01,($price/$count)*2);
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
            if ($productInfo){
                $promotion = $this->getBargainPromotion($record->promotion_id);
                $record->promotion = $promotion;
                $record->stock = Stock::find($promotion->stock_id);
                $record->product = Stock::find($promotion->product_id);
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
}