<?php
/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/8/8
 * Time: 下午4:07
 */

namespace App\Modules\Amount;


use App\Modules\Amount\Model\Amount;
use App\Modules\Amount\Model\AmountConfig;
use function GuzzleHttp\Psr7\uri_for;

trait AmountHandle
{
    public function getAmount($user_id)
    {
        return Amount::where('user_id','=',$user_id)->first();
    }
    public function setAmount($user_id,$price)
    {
        $amount = Amount::where('user_id','=',$user_id)->first();
        if (empty($amount)){
            $amount = new Amount();
            $amount -> user_id = $user_id;
        }
        $amount->amount = $price;
        if ($amount->save()){
            return true;
        }
        return false;
    }
    public function setAmountConfig($key,$value)
    {
        $config = AmountConfig::where('title','=',$key)->first();
        if (empty($config)){
            $config = new AmountConfig();
            $config->title = $key;
        }
        $config->content = $value;
        if ($config->save()){
            return true;
        }
    }
    public function getAmountConfig($key)
    {
        return AmountConfig::where('title','=',$key)->pluck('content')->first();
    }
}