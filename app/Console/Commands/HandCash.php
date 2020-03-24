<?php

namespace App\Console\Commands;

use App\Modules\Cash\CashHandle;
use Illuminate\Console\Command;

class HandCash extends Command
{
    use CashHandle;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Cash:hand';

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
        $openid="oHkUh5YKnkXB40f26nooYmRfwli8";
        $price=$this->getOrderPrice();
        $res = $this->handCash($openid,$price);
        $data=[
            'openid'=>$openid,
            'price'=>$price,
            'remarks'=>'',
            'created_at'=>date('Y-m-d H:i:s',time()),
            'updated_at'=>date('Y-m-d H:i:s',time())
        ];
        if($res['result_code'] == "SUCCESS"){
            $data['remarks']= '提现成功';
        }elseif($res['result_code'] == "FAIL"){
            $data['remarks']=$res["err_code_des"];
        }elseif($res['result_code'] == "ERROR"){
            $data['remarks']=$res['msg'];
        }
        $this->handle->saveCashList($data);

    }
}
