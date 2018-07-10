<?php
/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/7/9
 * Time: 下午6:23
 */

namespace App\Modules\Card;


use App\Modules\Card\Model\CardPromotion;

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
            return true;
        }
        return false;
    }
    public function getCardPromotion()
    {

    }
}