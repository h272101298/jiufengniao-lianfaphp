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
    public function addPickUpConfig()
    {
        $state = Input::get('state',0);
        if ($this->handle->setPickUpConfig($state)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function getPickUpConfig()
    {
        $data = $this->handle->getPickUpConfig();
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
}
