<?php
/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/6/23
 * Time: 下午4:23
 */

namespace App\Modules\Product;


use App\Modules\Product\Model\CategoryDetail;
use App\Modules\Product\Model\Product;
use App\Modules\Product\Model\ProductCategory;
use App\Modules\Product\Model\ProductCategorySnapshot;
use App\Modules\Product\Model\ProductDetailSnapshot;
use App\Modules\Product\Model\ProductType;
use App\Modules\Product\Model\ProductTypeBind;
use App\Modules\Product\Model\Stock;
use App\Modules\Product\Model\StockImage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait ProductHandle
{
    public function addProductType($id=0,$data,$parent=0)
    {
        if ($id!=0&&$id==$parent){
            return false;
        }
        if ($id){
            $type = ProductType::find($id);
        }else{
            $type = new ProductType();
        }
        foreach ($data as $key=>$value){
            $type->$key = $value;
        }
        if ($type->save()){
            ProductTypeBind::where('type_id','=',$type->id)->delete();
            $bind = new ProductTypeBind();
            $bind->type_id = $type->id;
            $bind->parent_id = $parent;
            $bind->save();
            return true;
        }
        return false;
    }
    public function delProductType($id)
    {
        $type = ProductType::findOrFail($id);
        if ($type->delete()){
            ProductTypeBind::where('type_id','=',$id)->delete();
            return true;
        }
        return false;
    }
    public function getProductTypes($page=1,$limit=10,$title='',$level=0,$parent=0)
    {
        $dbObj = DB::table('product_types');
        if ($title){
//            dd($title);
            $dbObj->where('title','like','%'.$title.'%');
        }
        if ($parent){
            $idArr = ProductTypeBind::where('parent_id','=',$parent)->pluck('type_id')->toArray();
            $dbObj->whereIn('id',$idArr);
        }
        if ($level){
            switch ($level){
                case 1:
                    $idArr = ProductTypeBind::where('parent_id','=',0)->pluck('type_id')->toArray();
                    break;
                case 2:
                    $swap = ProductTypeBind::where('parent_id','=',0)->pluck('type_id')->toArray();
                    $idArr = ProductTypeBind::whereIn('parent_id',$swap)->pluck('type_id')->toArray();
                    break;
                case 3:
                    $swap = ProductTypeBind::where('parent_id','=',0)->pluck('type_id')->toArray();
                    $swap = ProductTypeBind::whereIn('parent_id',$swap)->pluck('type_id')->toArray();
                    $idArr = ProductTypeBind::whereIn('parent_id',$swap)->pluck('type_id')->toArray();
                    break;
            }
            $dbObj->whereIn('id',$idArr);
        }
        $count = $dbObj->count();
        $types = $dbObj->limit($limit)->offset(($page-1)*$limit)->get();
        return [
            'data'=>$types,
            'count'=>$count
        ];
    }
    public function getProductTypesTree()
    {
        $level1 = ProductTypeBind::where('parent_id','=',0)->pluck('type_id')->toArray();
        $level2 = ProductTypeBind::whereIn('parent_id',$level1)->pluck('type_id')->toArray();
        $level3 = ProductTypeBind::whereIn('parent_id',$level2)->pluck('type_id')->toArray();
        $data = ProductType::all()->toArray();
        $bind = ProductTypeBind::all()->toArray();
        $bind = array_column($bind,'parent_id','type_id');
        for ($i=0;$i<count($data);$i++){
            $data[$i]['parent_id'] = $bind[$data[$i]['id']];
        }
//        dd($data);
        $level1 = array_filter($data,function ($item) use ($level1){
            return in_array($item['id'],$level1);
        });
        $level1 = array_merge($level1);
        $level2 = array_filter($data,function ($item) use ($level2){
            return in_array($item['id'],$level2);
        });
        $level2 = array_merge($level2);
        $level3 = array_filter($data,function ($item) use ($level3){
            return in_array($item['id'],$level3);
        });
        $level3 = array_merge($level3);
        for ($i=0;$i<count($level2);$i++){
            $id = $level2[$i]['id'];
            $level2[$i]['child'] = array_filter($level3,function ($item) use ($id){
                return $item['parent_id']==$id;
            });
        }
        for ($i=0;$i<count($level1);$i++){
            $id = $level1[$i]['id'];
            $level1[$i]['child'] = array_filter($level2,function ($item) use ($id){
                return $item['parent_id']==$id;
            });
        }
        return $level1;
    }
    public function addProductCategory($id=0,$type_id,$title,$store_id=0,$detail=[])
    {
        if ($id){
            $category = ProductCategory::find($id);
        }else{
            $category = new ProductCategory();
        }
        $category->title = $title;
        $category->store_id = $store_id;
        $category->type_id = $type_id;
        if ($category->save()){
            CategoryDetail::where('category_id','=',$category->id)->delete();
            foreach ($detail as $item){
                $detail = new CategoryDetail();
                $detail->category_id = $category->id;
                $detail->title = $item;
                $detail->save();
            }
            return true;
        }
        return false;
    }
    public function getProductCategories($store_id=0,$page=1,$limit=10,$title='')
    {
        $db = DB::table('product_categories')->where('store_id','=',$store_id);
        if ($title){
            $db->where('title','like','%'.$title.'%');
        }
        $count = $db->count();
        $data = $db->limit($limit)->offset(($page-1)*$limit)->get();
        if (!empty($data)){
            $this->formatProductCategory($data);
        }
        return [
            'data'=>$data,
            'count'=>$count
        ];
    }
    public function delProductCategory($id)
    {
        $category = ProductCategory::findOrFail($id);
        if ($category->delete()){
            CategoryDetail::where('category_id','=',$id)->delete();
            return true;
        }
        return false;
    }
    public function formatProductCategory(&$data)
    {
        foreach ($data as $datum){
            $array = CategoryDetail::where('category_id','=',$datum->id)->get()->toArray();
            $detail = array_column($array,'title');
            $datum->detail = implode(',',$detail);
            $datum->detailArray = $array;
        }
        return $data;
    }
    public function addProductCategorySnapshot($product_id,$detail_id)
    {
        $detail = CategoryDetail::findOrFail($detail_id);
        $category = ProductCategory::findOrFail($detail->category_id);
        $categorySnap = ProductCategorySnapshot::where('product_id','=',$product_id)->where('title','=',$category->title)->first();
        if (empty($categorySnap)){
            $categorySnap = new ProductCategorySnapshot();
            $categorySnap->product_id = $product_id;
            $categorySnap->title = $category->title;
            $categorySnap->save();
        }
        $detailSnap = ProductDetailSnapshot::where('category_id','=',$categorySnap->id)->where('title','=',$detail->title)->first();
        if (empty($detailSnap)){
            $detailSnap  = new ProductDetailSnapshot();
            $detailSnap->category_id = $categorySnap->id;
            $detailSnap->title = $detail->title;
            $detailSnap->save();
        }
        return true;
    }
    public function addProduct($id=0,$data)
    {
        if ($id){
            $product = Product::find($id);
        }else{
            $product = new Product();
        }
        foreach ($data as $key=>$value){
            $product->$key = $value;
        }
        if ($product->save()){
            return $product->id;
        }
        return false;
    }
    public function addStock($id,$data)
    {
        if ($id){
            $stock = Stock::find($id);
        }else{
            $stock = new Stock();
        }
        foreach ($data as $key => $value){
            $stock->$key = $value;
        }
        if ($stock->save()){
            return $stock->id;
        }
        return false;
    }
    public function addStockImage($id,$url)
    {
        $image = new StockImage();
        $image->stock_id = $id;
        $image->url = $url;
        if ($image->save()){
            return true;
        }
        return false;
    }
}