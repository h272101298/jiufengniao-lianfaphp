<?php

namespace App\Http\Controllers\V3;

use App\Modules\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
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
    public function addWithdraw(Request $post)
    {
        $price = $post->price;
        $amount = $this->handle->getStoreAmount(getStoreId());
        if ($price>$amount->available){
            return jsonResponse([
                'msg'=>'可提现余额不足！'
            ]);
        }
        $data = [
            'store_id'=>getStoreId(),
            'price'=>$price,
            'bank'=>$post->bank,
            'account'=>$post->account,
            'remark'=>''
        ];
        if ($this->handle->addStoreWithdraw(0,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function getWithdraws()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $state = Input::get('state',0);
        $store_id = getStoreId();
        if (checkPermission(Auth::id(),'StoreWithdrawAll')){
            $store_id = 0;
        }
        $data = $this->handle->getStoreWithdraws($page,$limit,$store_id,$state);
        $this->handle->formatStoreWithdraws($data['data']);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function checkWithdraw()
    {
        $id = Input::get('id');
        $state = Input::get('state');
        $withdraw = $this->handle->getStoreWithdraw($id);
        if ($state==1){
            $this->handle->setStoreAmountAvailable($withdraw->store_id,$withdraw->price);
        }
        $this->handle->addStoreWithdraw($id,['state'=>$state]);
        return jsonResponse([
            'msg'=>'ok'
        ]);
    }
}
