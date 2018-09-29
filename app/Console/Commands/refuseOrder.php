<?php

namespace App\Console\Commands;

use App\Modules\Order\Model\Order;
use App\Modules\Order\Model\RefuseList;
use App\Modules\System\Model\TxConfig;
use Illuminate\Console\Command;

class refuseOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refuseOrder';

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
        $lists = RefuseList::where('state','=',0)->get();
        foreach ($lists as $list){
            $config = TxConfig::first();
            $wxpay = getWxPay();
            $path = base_path().'/public/';
            $order = Order::find($list->order_id);
            $total_fee = Order::where('group_number','=',$order->group_number)->sum('price');
            $data = $wxpay->refund($order->transaction_id,$order->number,$total_fee*100,$order->price*100,$config->mch_id,$path.$config->ssl_cert,
                $path.$config->ssl_key);
            if ($data['return_code']=='FAIL'){
                $list->state = 3;
                $list->save();
            }else{
                if ($data['result_code']=='FAIL'){
                    $list->state = 3;
                    $list->save();
                }else{
                    $list->state = 2;
                    $list->save();
                    Order::where('group_number','=',$order->group_number)->update(['state'=>'canceled']);
                    $amount = StoreAmount::where('store_id','=',$order->store_id)->first();
                    if (empty($amount)){
//                        $count = Order::where('store_id','=',$store->id)->whereNotIn('state',['canceled','created'])->sum('price');
                        $amount = new StoreAmount();
                        $amount->store_id = $order->store_id;
                    }
                    $amount->amount -= $order->price;
                    $amount->available -= $order->price;
                    $amount->save();
                }
            }
        }
    }
}
