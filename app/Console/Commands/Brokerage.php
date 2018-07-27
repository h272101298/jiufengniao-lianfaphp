<?php

namespace App\Console\Commands;

use App\Modules\Order\Model\Order;
use App\Modules\Order\Model\StockSnapshot;
use App\Modules\Product\Model\Product;
use App\Modules\Proxy\Model\BrokerageQueue;
use App\Modules\Proxy\Model\BrokerageRatio;
use App\Modules\Proxy\Model\ProxyList;
use App\Modules\WeChatUser\Model\WeChatUser;
use Carbon\Carbon;
use Illuminate\Console\Command;

class Brokerage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'brokerage:make';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $config = BrokerageRatio::first();

        $queues = BrokerageQueue::where('state','=',1)->where('created_at','<',Carbon::parse(date('Y-m-d',strtotime('-7 days'))))->get();
//        dd($queues);
        foreach ($queues as $queue){
            $order = Order::find($queue->order_id);
            $swap = ProxyList::where('user_id','=',$order->user_id)->pluck('proxy_id')->first();
            $user = WeChatUser::find($swap);
            $list = $this->getUsers($user);
            $nickname = '';
            for ($i=0;$i<count($list);$i++){
                $swap = $i+1;
                $name = 'level'.$swap;
                $ratio = $config->$name;
                $price = 0;
                $stocks = StockSnapshot::where('order_id','=',$order->id)->get();
                foreach ($stocks as $stock){
                    $product = Product::find($stock->product_id);
                    if ($product->brokerage==0){
                        $price = 0;
                    }else{
                        $price += $stock->price*($product->brokerage/100)*$stock->number;
                    }
                }
                $price = $price*($ratio/100);
                $log = new \App\Modules\Proxy\Model\Brokerage();
                $log->user_id = $list[$i]->id;
                $log->order_id = $order->id;
                $log->brokerage = $price;
                if ($i==0){
                    $log->type = 1;
                    $nickname = $list[$i]->nickname;
                }elseif($i==1){
                    $log->type = 2;
                }else{
                    $log->type = 3;
                    if ($name!=''){
                        $log->remark = '来自'.$nickname.'的奖励';
                    }
                }
                $log->save();

            }
            $queue->state = 2;
            $queue->save();
        }
    }
    public function getUsers($user,&$data=[])
    {
        for ($i=0;$i<=2;$i++){
            if (!empty($user)){
                $swap = ProxyList::where('user_id','=',$user->id)->pluck('proxy_id')->first();
                if ($swap!=0){
                    array_push($data,$user);
                    $user = WeChatUser::find($swap);
                }else{
                    array_push($data,$user);
                    $user = null;
                }
            }
        }
        return $data;
    }
}
