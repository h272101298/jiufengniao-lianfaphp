<?php

namespace App\Console\Commands;

use App\Modules\Order\Model\Order;
use App\Modules\Order\Model\StockSnapshot;
use App\Modules\Product\Model\Product;
use App\Modules\Store\Model\Store;
use App\Modules\Store\Model\StoreAmount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Upgrade extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Upgrade';

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

        //dd();
//        try{
//            //第一次
//            DB::select('alter table bargain_promotions drop column stock_id,drop column min_price,drop column origin_price,drop column start,drop column `end`');
//            DB::select(' alter table bargain_promotions add `time` int DEFAULT 0');
//            DB::select(' alter table stock_snapshots add `product` VARCHAR(50) DEFAULT NULL ');
//            DB::select(' alter table proxy_applies add `notify_id` VARCHAR(194) DEFAULT NULL ');
            $snapshots = StockSnapshot::all();
            foreach ($snapshots as $snapshot){
                $product = Product::find($snapshot->product_id);
                if (!empty($product)&&empty($snapshot->product)){
                    $snapshot->product = $product->name;
                    $snapshot->save();
                }
            }
//            DB::select(' alter table orders add `delivery` tinyint(4) DEFAULT 0 ');
//            DB::select(' alter table exchange_records add `score` int(11)  DEFAULT 0 ');
//        //第二次更新
          $stores = Store::all();
          foreach ($stores as $store){
              $amount = StoreAmount::where('store_id','=',$store->id)->first();
              if (empty($amount)){
                  $count = Order::where('store_id','=',$store->id)->whereNotIn('state',['canceled','created'])->sum('price');
                  $amount = new StoreAmount();
                  $amount->store_id = $store->id;
                  $amount->amount = $count;
                  $amount->available = $count;
                  $amount->save();
              }else{
                  $count = Order::where('store_id','=',$store->id)->whereNotIn('state',['canceled','created'])->sum('price');
                  $amount->amount += $count;
                  $amount->available += $count;
                  $amount->save();
              }
          }
//            //第三次 更新 增加商品推荐排序
//            DB::select(' alter table offer_lists add `sort` int(11)  DEFAULT 0 ');
//            //第四次更新 增加广告跳转商品
//            DB::select(' alter table adverts add `product_id` int(11)  DEFAULT 0 ');
//        }catch (\Exception $exception){
//            echo $exception->getMessage();
//        }
        //更新 抽奖奖品头像
        //DB::select(' alter table prizes add `icon` VARCHAR(500) DEFAULT NULL ');

    }
}
