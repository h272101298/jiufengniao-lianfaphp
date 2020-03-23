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

        dd($res);

    }
}
