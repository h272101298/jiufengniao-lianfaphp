<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/23
 * Time: 15:38
 */

namespace App\Modules\Cash;


use App\Modules\Cash\Model\Cash;
use App\Modules\Order\Model\Order;
use App\Modules\System\Model\TxConfig;
use Illuminate\Support\Facades\DB;

trait CashHandle
{
    public function handCash($open_id,$amount){
        $config=TxConfig::first();
        $path=base_path().'/public/';
        $wxpay = getWxPay();
        if ($amount > 1){
            //$amount=$amount*100;
            $data = $wxpay->handCash($open_id,$amount,$path.$config->ssl_cert,$path.$config->ssl_key);
        }else{
            $data = [
                'result_code'=>"ERROR",
                'msg'=>"金额少于1元,无法提现"
            ];
        }
        return $data;
    }
    public function getOrderPrice(){
        $start_time=date('Y-m-d 00:00:00',time());
        $end_time=date('Y-m-d 23:59:59',time());
        $amount=DB::table('orders')->where('created_at','>=',$start_time)
            ->where('created_at','<',$end_time)
            ->where('state','!=','canceled')
            ->where('state','!=','created')
            ->sum('price');
        return $amount;
    }
    public function saveCashList($data){
        $res=DB::table('hand_cash_list')->insert($data);
        if ($res){
            return $res;
        }
    }
}