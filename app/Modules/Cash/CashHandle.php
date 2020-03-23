<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/23
 * Time: 15:38
 */

namespace App\Modules\Cash;


use App\Modules\System\Model\TxConfig;

trait CashHandle
{
    public function handCash($open_id){
        $config=TxConfig::first();
        $path=base_path().'/pubilc/';
        $wxpay = getWxPay();
        $data = $wxpay->handCash($open_id,"100",$path.$config->ssl_cert,$path.$config->ssl_key);
        return $data;
    }
}