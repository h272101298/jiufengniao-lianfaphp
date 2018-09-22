<?php

namespace App\Http\Controllers\V3;

use App\Modules\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class ExcelController extends Controller
{
    //
    public function __construct(\Maatwebsite\Excel\Excel $excel)
    {
        $this->excel = $excel;
        $this->handle = new User();
    }
    public function exportOrder()
    {
        $search = Input::get('search');
        $page = Input::get('page', 1);
        $limit = Input::get('limit', 1000);
        $start = Input::get('start');
        $end = Input::get('end');
        $idArray = [];
        if ($search){
            $idArr = $this->handle->getOrderIdByExpressName($search);
            $idArray = $this->handle->getOrderIdByStoreName($search);
            $idArray = array_merge($idArr, $idArray);
        }
        $state = Input::get('state');
        $type = Input::get('type');
        if ($type){
            $ordersId = $this->handle->getOrdersIdByOrderType($type);
            $idArray = $ordersId;
        }
        $data = $this->handle->getOrders($page, $limit, $start, $end, $search, $idArray,null,$state,getStoreId());
        $data = $this->handle->formatExcelOrders($data['data']);
        $tr = [['id','订单号','用户名','总计','收货方式','订单状态','下单时间','收货人','联系方式','收货地址']];
        $data = array_merge($tr,$data);
        $this->excel->create('orders',function ($excel) use ($tr,$data){
            $excel->sheet('sheet1',function ($sheet) use ($data){
                $count = count($data);
                for ($j=0;$j<$count;$j++){
                    $sheet->row($j+1,$data[$j]);
                }
            });
        })->export('xls');
    }
}
