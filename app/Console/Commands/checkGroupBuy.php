<?php

namespace App\Console\Commands;

use App\Modules\GroupBuy\Model\GroupBuyJoin;
use App\Modules\GroupBuy\Model\GroupBuyList;
use App\Modules\GroupBuy\Model\GroupBuyPromotion;
use App\Modules\Order\Model\RefuseList;
use Illuminate\Console\Command;

class checkGroupBuy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'checkGroupBuy';

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
        $lists = GroupBuyList::where('state','=',1)->get();
        foreach ($lists as $list){
            $promotion = GroupBuyPromotion::find($list->group_id);
            if (!empty($promotion)){
                $joinNumber = GroupBuyJoin::where('state','=',1)->where('list_id','=',$list->id)->count();
                if ($joinNumber>=$promotion->people_number){
                    $list->state = 2;
                }else{
                    if ($list->end<time()){
                        $list->state = 3;
                        $joins = GroupBuyJoin::where('state','=',1)->where('list_id','=',$list->id)->get();
                        foreach ($joins as $join){
                            $refuse = new RefuseList();
                            $refuse->order_id = $join->order_id;
                            $refuse->save();
                        }
                    }
                }
            }else{
                $list->state = 3;
                $joins = GroupBuyJoin::where('state','=',1)->where('list_id','=',$list->id)->get();
                foreach ($joins as $join){
                    $refuse = new RefuseList();
                    $refuse->order_id = $join->order_id;
                    $refuse->save();
                }
            }
            $list->save();
        }
    }
}
