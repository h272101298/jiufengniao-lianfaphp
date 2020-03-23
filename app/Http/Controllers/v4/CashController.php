<?php

namespace App\Http\Controllers\v4;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CashController extends Controller
{
    //
    private $open_id="oHkUh5bWFz3HjGMb9tW7RMbA-fUg";
    public function handCash(){
        //$wx=getWxXcx();
        $mch=getWxPay();
        $order_id=time();
        dd($mch->handCash($this->open_id,'100'));

    }
}
