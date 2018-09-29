<?php
/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/8/17
 * Time: 下午2:29
 */

namespace App\Modules\Score;


use App\Modules\Order\Model\OrderType;
use App\Modules\Product\Model\CategoryDetail;
use App\Modules\Product\Model\ProductCategory;
use App\Modules\Score\Model\ExchangeRecord;
use App\Modules\Score\Model\ScoreConfig;
use App\Modules\Score\Model\ScoreProduct;
use App\Modules\Score\Model\ScoreProductCategorySnapshot;
use App\Modules\Score\Model\ScoreProductDetailSnapshot;
use App\Modules\Score\Model\ScoreProductStock;
use App\Modules\Score\Model\ScoreRecord;
use App\Modules\Score\Model\ScoreStockImage;
use App\Modules\Score\Model\ShareRecord;
use App\Modules\Score\Model\UserScore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use function PHPSTORM_META\type;

trait ScoreHandle
{
    public function getUserScore($user_id)
    {
        $score = UserScore::where('user_id','=',$user_id)->pluck('score')->first();
        if (empty($score)){
            return 0;
        }
        return $score;
    }
    public function addUserScore($user_id,$score)
    {
        $userScore = UserScore::where('user_id','=',$user_id)->first();
        if (empty($userScore)){
            $userScore = new UserScore();
            $userScore->score = 0 ;
            $userScore->user_id = $user_id;
        }
        $userScore->score = $score;
        if ($userScore->save()){
            return true;
        }
        return false;
    }
    public function addUserScore2($user_id,$score)
    {
        $userScore = UserScore::where('user_id','=',$user_id)->first();
        if (empty($userScore)){
            $userScore = new UserScore();
            $userScore->score = 0 ;
            $userScore->user_id = $user_id;
        }
        $userScore->score += $score;
        if ($userScore->save()){
            return true;
        }
        return false;
    }
    public function addScoreProduct($id,$data)
    {
        if ($id){
            $product = ScoreProduct::find($id);
        }else{
            $product = new ScoreProduct();
        }
        foreach ($data as $key=>$value){
            $product->$key = $value;
        }
        if ($product->save()){
            return $product->id;
        }
        return false;
    }
    public function addScoreProductStock($id,$data)
    {
        if ($id){
            $stock = ScoreProductStock::find($id);
        }else{
            $stock = new ScoreProductStock();
        }
        foreach ($data as $key=>$value){
            $stock->$key = $value;
        }
        if ($stock->save()){
            return $stock->id;
        }
        return false;
    }
    public function getScoreProducts($page=1,$limit=10,$delete=0,$store_id=0,$state=0,$hot=0)
    {
        $db = DB::table('score_products');
        if ($delete){
            $db->where('deleted','=',$delete-1);
        }
        if ($store_id){
            $db->where('store_id','=',$store_id);
        }
        if ($state){
            $db->where('state','=',$state-1);
        }
        if ($hot){
            $db->where('hot','=',$hot);
        }
        $count = $db->count();
        $data = $db->orderBy('id','DESC')->limit($limit)->offset(($page-1)*$limit)->get();
        return [
            'count'=>$count,
            'data'=>$data
        ];
    }
    public function getScoreProduct($id)
    {
        return ScoreProduct::findOrFail($id);
    }
    public function getScoreProductStocks($product_id,$detail=0)
    {
        $stocks = ScoreProductStock::where('product_id','=',$product_id)->get();
        if (!empty($stocks)){
            foreach ($stocks as $stock){
                $stock->images = ScoreStockImage::where('stock_id','=',$stock->id)->get();
                if ($detail){
                    $swap = $stock->product_detail;
                    if ($swap!='fixed'){
//                        dd($swap);
                        $swap = explode(',',$swap);
//                        dd($swap);
                        $title = ScoreProductDetailSnapshot::whereIn('id',$swap)->pluck('title')->toArray();
                        $stock->title = $title;
                    }
                }
            }
        }
        return $stocks;
    }
    public function formatScoreProducts(&$products)
    {
        if(empty($products)){
            return [];
        }
        foreach ($products as $product){
            $stock = ScoreProductStock::where('product_id','=',$product->id)->orderBy('score','ASC')->first();
            if (!empty($stock)){

                $product->cover = $stock->cover;
                $product->score = $stock->score;
            }
            $product->exchange = OrderType::where('type','=','scoreOrder')->where('promotion_id','=',$product->id)->count();
        }
        return $products;
    }
    public function addScoreProductCategorySnapshot($product_id, $detail_id)
    {
        $detail = CategoryDetail::findOrFail($detail_id);
        $category = ProductCategory::findOrFail($detail->category_id);
        $categorySnap = ScoreProductCategorySnapshot::where('product_id', '=', $product_id)->where('title', '=', $category->title)->first();
        if (empty($categorySnap)) {
            $categorySnap = new ScoreProductCategorySnapshot();
            $categorySnap->product_id = $product_id;
            $categorySnap->title = $category->title;
            $categorySnap->save();
        }
        $detailSnap = ScoreProductDetailSnapshot::where('category_id', '=', $categorySnap->id)->where('title', '=', $detail->title)->first();
        if (empty($detailSnap)) {
            $detailSnap = new ScoreProductDetailSnapshot();
            $detailSnap->category_id = $categorySnap->id;
            $detailSnap->title = $detail->title;
            $detailSnap->save();
        }
        return $detailSnap->id;
    }
    public function delScoreStockImages($stock_id)
    {
        return ScoreStockImage::where('stock_id','=',$stock_id)->delete();
    }
    public function delScoreProductStocks($product_id)
    {
        $stocks = ScoreProductStock::where('product_id','=',$product_id)->get();
        foreach ($stocks as $stock){
            ScoreStockImage::where('stock_id','=',$stock->id)->delete();
            $stock->delete();
        }
    }
    public function addScoreStockImage($id, $url)
    {
        $image = new ScoreStockImage();
        $image->stock_id = $id;
        $image->url = $url;
        if ($image->save()) {
            return true;
        }
        return false;
    }
    public function getScoreProductCategories($product_id)
    {
        $categories = ScoreProductCategorySnapshot::where('product_id', '=', $product_id)->get();
        if (!empty($categories)) {
            foreach ($categories as $category) {
                $category->detail = ScoreProductDetailSnapshot::where('category_id', '=', $category->id)->get();
            }
        }
        return $categories;
    }
    public function getScoreStock($product, $detail)
    {
        $stock = ScoreProductStock::where('product_id', '=', $product)->where('product_detail', '=', $detail)->first();
        if (!empty($stock)) {
            $stock->images = ScoreStockImage::where('stock_id', '=', $stock->id)->pluck('url')->toArray();
        }
        return $stock;
    }
    public function addExchangeRecord($id,$data)
    {
        if ($id){
            $record = ExchangeRecord::find($id);
        }else{
            $record = new ExchangeRecord();
        }
        foreach ($data as $key => $value){
            $record->$key = $value;
        }
        if ($record->save()){
            return true;
        }
        return false;
    }
    public function getExchangeRecordCount($user_id=0,$product_id=0)
    {
        $db = DB::table('exchange_records');
        if ($user_id){
            $db->where('user_id','=',$user_id);
        }
        if ($product_id) {
            $db->where('product_id','=',$product_id);
        }
        return $db->count();
    }
    public function getOrderExchangeRecord($order_id)
    {
        return ExchangeRecord::where('order_id','=',$order_id)->first();
    }
    public function getScoreConfig()
    {
        return ScoreConfig::first();
    }
    public function setScoreConfig($state,$ratio)
    {
        $config = ScoreConfig::first();
        if (empty($config)){
            $config = new ScoreConfig();
        }
        $config->state = $state;
        $config->ratio = $ratio;
        if ($config->save()){
            return true;
        }
        return false;
    }
    public function addScoreRecord($id=0,$data)
    {
        if ($id){
            $record = ScoreRecord::find($id);
        }else{
            $record = new ScoreRecord();
        }
        foreach ($data as $key => $value){
            $record->$key = $value;
        }
        if ($record->save()){
            return true;
        }
        return false;
    }
    public function getScoreRecords($user_id=0,$type=0,$page=1,$limit=10)
    {
        $db = DB::table('score_records');
        if ($user_id){
            $db->where('user_id','=',$user_id);
        }
        if ($type){
            $db->where('type','=',$type);
        }
        $count = $db->count();
        $data = $db->orderBy('id','DESC')->limit($limit)->offset(($page-1)*$limit)->get();
        return [
            'count'=>$count,
            'data'=>$data
        ];
    }
    public function addScoreUseRecord($id=0,$data)
    {
        if ($id){
            $record = ScoreRecord::find($id);
        }else{
            $record = new ScoreRecord();
        }
        foreach ($data as $key => $value){
            $record->$key = $value;
        }
        if ($record->save()){
            return true;
        }
        return false;
    }
    public function getScoreUseRecords($user_id=0,$type=0,$page=1,$limit=10)
    {
        $db = DB::table('score_use_records');
        if ($user_id){
            $db->where('user_id','=',$user_id);
        }
        if ($type){
            $db->where('type','=',$type);
        }
        $count = $db->count();
        $data = $db->orderBy('id','DESC')->limit($limit)->offset(($page-1)*$limit)->get();
        return [
            'count'=>$count,
            'data'=>$data
        ];
    }
    public function addShareRecord($user_id,$share_id,$page)
    {
        $record = new ShareRecord();
        $record->user_id = $user_id;
        $record->share_id = $share_id;
        $record->page = $page;
        if ($record->save()){
            return true;
        }
        return false;
    }
    public function checkShareRecord($user_id,$share_id,$page)
    {
        return ShareRecord::where('user_id','=',$user_id)
            ->where('share_id','=',$share_id)
            ->where('page','=',$page)->count();
    }
}