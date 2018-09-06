<?php

namespace App\Http\Controllers\V3;

use App\Modules\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class StoreController extends Controller
{
    //
    private $handle;
    public function __construct()
    {
        $this->handle = new User();
    }
    public function setStoreExpress()
    {
        $store_id = getStoreId();
        $express_id = Input::get('express_id');
        $price = Input::get('price');
        if ($this->handle->setStoreExpress($store_id,$express_id,$price)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function getStoreExpress()
    {
        $data = $this->handle->getStoreExpress(getStoreId());
        $this->handle->formatStoreExpress($data);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
}
