<?php
/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/6/12
 * Time: 上午11:46
 */

namespace App\Modules\Advert;


use App\Modules\Advert\Model\Advert;
//use App\Modules\Advert\Model\Category;
use App\Modules\Advert\Model\CategoryAdvert;
use Illuminate\Support\Facades\DB;

trait AdvertHandle
{
    public function getAdverts($page,$limit,$type,$category_id)
    {
        $dbObj = DB::table('adverts');
        if ($category_id){
            $idArr = CategoryAdvert::where('category_id','=',$category_id)->pluck('advert_id')->toArray();
            $dbObj->whereIn('id',$idArr);
        }
        if ($type){
            $dbObj->where('type','=',$type);
        }
        $count = $dbObj->count();
        $data = $dbObj->limit($limit)->offset(($page-1)*$limit)->get();
        return [
            'data'=>$data,
            'count'=>$count
        ];
    }
    public function createAdvert($id=0,$data,$category_id)
    {
        if ($id){
            $advert = Advert::findOrFail($id);
        }else{
            $advert = new Advert();
        }
        foreach ($data as $key=>$value){
            $advert->$key = $value;
        }
        if ($advert->save()){
            if ($category_id){
                CategoryAdvert::where('advert_id','=',$advert->id)->delete();
                $categoryAdvert = new CategoryAdvert();
                $categoryAdvert->category_id = $category_id;
                $categoryAdvert->advert_id = $advert->id;
                $categoryAdvert->save();
            }
            return true;
        }
        return false;
    }
    public function delAdvert($id)
    {
        $advert = Advert::findOrFail($id);
        if ($advert->delete()){
            CategoryAdvert::where('advert_id','=',$advert->id)->delete();
            return true;
        }
        return false;
    }

}