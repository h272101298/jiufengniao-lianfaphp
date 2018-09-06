<?php

namespace App\Http\Controllers\V3;

use App\Modules\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DiscountController extends Controller
{
    //
    private $handle;
    public function __construct()
    {
        $this->handle = new User();
    }
    public function addDiscountConfig(Request $post)
    {
        $type = $post->type;
        $ratio = $post->ratio;
        $items = $post->items;
        if ($this->handle->addDiscountConfig([
            'type'=>$type,
            'ratio'=>$ratio
        ])){
            $this->handle->delDisCountItem();
            foreach ($items as $item){
                $this->handle->addDiscountItem($item);
            }
        }
        return jsonResponse([
            'msg'=>'200'
        ]);
    }
    public function getDiscountConfig()
    {
        $data = $this->handle->getDiscountConfig();
        $data->items = $this->handle->getDisCountItems();
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
}
