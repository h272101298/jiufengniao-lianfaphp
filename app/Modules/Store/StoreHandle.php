<?php
/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/6/21
 * Time: 下午2:47
 */

namespace App\Modules\Store;


use App\Modules\Store\Model\StoreCategory;

trait StoreHandle
{
    public function addStoreCategory($id=0,$title)
    {
        if ($id){
            $category = StoreCategory::find($id);
        }else{
            $category = new StoreCategory();
        }
        $category->title = $title;
        if ($category->save()){
            return true;
        }
        return false;
    }
    public function getStoreCategories($page,$limit)
    {
        return [
          'count'=>StoreCategory::count(),
          'data'=>StoreCategory::limit($limit)->offset(($page-1)*$limit)->get()
        ];
    }
    public function delStoreCategory($id)
    {
        $category = StoreCategory::findOrFail($id);
        if ($category->delete()){
            return true;
        }
        return false;
    }
}