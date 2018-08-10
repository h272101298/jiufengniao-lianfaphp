<?php

namespace App\Http\Controllers\V3;

use App\Modules\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class SystemController extends Controller
{
    //
    private $handle;
    public function __construct()
    {
        $this->handle = new User();
    }

    public function addAmountConfig()
    {
        $key = Input::get('title');
        $value = Input::get('content');
        if ($this->handle->setAmountConfig($key,$value)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        };
    }
}
