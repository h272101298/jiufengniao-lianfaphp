<?php

namespace App\Console\Commands;

use App\Modules\Order\Model\StockSnapshot;
use App\Modules\Product\Model\Product;
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
        DB::select('alter table bargain_promotions drop column stock_id,drop column min_price,drop column origin_price,drop column start,drop column `end`');
        DB::select(' alter table bargain_promotions add `time` int DEFAULT 0');
        DB::select(' alter table stock_snapshots add `product` VARCHAR(50) DEFAULT NULL ');
        $snapshots = StockSnapshot::all();
        foreach ($snapshots as $snapshot){
            $product = Product::find($snapshot->product_id);
            if (!empty($product)){
                $snapshot->product = $product->name;
                $snapshot->save();
            }
        }
        DB::select(' alter table orders add `delivery` tinyint(4) DEFAULT 0 ');
        DB::select(' alter table exchange_records add `score` int(11)  DEFAULT 0 ');
    }
}
