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
        $id="39.104.98.40";
        $order_id="T2020".time();
        $amount=500;
        $desc="ceshi";
        $sllCert=$path.$config->ssl_cert;
        $sllKey=$path.$config->ssl_key;
        dd($sllCert);
        $data = $wxpay->transfer($open_id,$order_id,$amount,$desc,$id,$sllCert,$sllKey);
        return $data;
    }
}