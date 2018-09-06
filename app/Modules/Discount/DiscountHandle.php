<?php
/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/9/6
 * Time: 下午7:08
 */

namespace App\Modules\Discount;


use App\Modules\Discount\Model\DiscountConfig;
use App\Modules\Discount\Model\DiscountItem;

trait DiscountHandle
{
    public function addDiscountConfig($data)
    {
        $config = DiscountConfig::first();
        if (empty($config)){
            $config = new DiscountConfig();
        }
        foreach ($data as $key =>$value){
            $config->$key = $value;
        }
        if ($config->save()){
            return true;
        }
        return false;
    }
    public function getDiscountConfig()
    {
        return DiscountConfig::first();
    }
    public function delDisCountItem()
    {
        return DiscountItem::truncate();
    }
    public function getDisCountItems()
    {
        return DiscountItem::pluck('item')->toArray();
    }
    public function addDiscountItem($item)
    {
        $discountItem = DiscountItem::where('item','=',$item)->first();
        if (empty($discountItem)){
            $discountItem = new DiscountItem();
            $discountItem->item = $item;
            $discountItem->save();
            return true;
        }
        return true;
    }
}