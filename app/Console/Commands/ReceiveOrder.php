<?php

namespace App\Console\Commands;

use App\Modules\Order\Model\Order;
use Illuminate\Console\Command;

class ReceiveOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'receiveOrder';

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
        $orders = Order::where('state','=','delivery')->where('created_at','<',Carbon::parse(date('Y-m-d',strtotime('-3 days'))))->get();
        foreach ($orders as $order){
            $order->state = 'finished';
            $order->save();
        }
    }
}
