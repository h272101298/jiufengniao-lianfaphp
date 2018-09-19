<?php

namespace App\Http\Controllers\V2;

use App\Modules\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class ProductController extends Controller
{
    //
    private $handle;
    public function __construct()
    {
        $this->handle = new User();
    }
    public function getStockByProduct()
    {
        $product_id = Input::get('product_id');
        $data = $this->handle->getStocksByProductId($product_id);
        $this->handle->formatStocks($data);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
}
