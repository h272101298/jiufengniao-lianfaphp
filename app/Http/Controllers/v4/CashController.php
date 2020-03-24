<?php

namespace App\Http\Controllers\v4;

use App\Modules\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CashController extends Controller
{
    //
    private $open_id="oHkUh5bWFz3HjGMb9tW7RMbA-fUg";
    private $handle;

    public function __construct()
    {
        $this->handle = new User();
    }
    public function handCash(){
        $price=$this->handle->getOrderPrice();
        $res = $this->handle->handCash($this->open_id,$price);
        $data=[
            'openid'=>$this->open_id,
            'price'=>$price,
            'remarks'=>'',
            'created_at'=>date('Y:m:d H:i:s',time()),
            'update_at'=>date('Y:m:d H:i:s',time())
        ];
dd($res);
        if($res['result_code'] == "SUCCESS"){
            $data['remarks']= '提现成功';
        }elseif($res['result_code'] == "FAIL"){
            $data['remarks']=$res["err_code_des"];
        }else{
            $data['remarks']=$res['msg'];
        }

        $msg=$this->handle->saveCashList($data);
        dd($msg);

    }
}
