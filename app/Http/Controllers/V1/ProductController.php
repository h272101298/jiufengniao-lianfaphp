<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\ProductCategoryPost;
use App\Http\Requests\ProductTypePost;
use App\Libraries\Wxxcx;
use App\Modules\Product\Model\Product;
use App\Modules\Store\Model\Store;
use App\Modules\User;
use function foo\func;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;

class ProductController extends Controller
{
    //
    private $handle;
    public function __construct()
    {
        $this->handle = new User();
    }
    public function createProductType(ProductTypePost $post)
    {
        $id = $post->id?$post->id:0;
        $parent = $post->parent?$post->parent:0;
        $data = [
            'title'=>$post->title,
            'logo'=>$post->logo?$post->logo:''
        ];
        if ($this->handle->addProductType($id,$data,$parent)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'操作失败！'
        ],400);
    }
    public function getProductTypes()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $title = Input::get('title');
        $level = Input::get('level',0);
        $parent = Input::get('parent',0);
        $types = $this->handle->getProductTypes($page,$limit,$title,$level,$parent);
        $this->handle->formatProductTypes($types['data']);
        return response()->json([
            'msg'=>'ok',
            'data'=>$types
        ]);
    }
    public function delProductType()
    {
        $id = Input::get('id');
        if ($this->handle->checkTypeChild($id)){
            return jsonResponse([
                'msg'=>'当前分类存在子分类！'
            ],400);
        }
        if ($this->handle->countTypeProducts($id)){
            return jsonResponse([
                'msg'=>'当前分类下存在商品！'
            ],400);
        }
        if ($this->handle->delProductType($id)){
            $this->handle->delHotType($id);
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'操作失败！'
        ],400);
    }
    public function getProductTypesTree()
    {
        $data = $this->handle->getProductTypesTree();
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function getProductTypesParents()
    {
        $data = $this->handle->getProductTypesParents();
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function getProductTypesTreeByParent()
    {
        $id = Input::get('id');
        $data = $this->handle->getProductTypesTreeByParent($id);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function addProductCategory(ProductCategoryPost $post)
    {
        $categories = $post->categories;
        $type_id = $post->type_id;
        foreach ($categories as $category){
            $id = isset($category['id'])?$category['id']:0;
            $detail = $category['detail'];
            $detail = array_column($detail,'content');
            $this->handle->addProductCategory($id,$type_id,$category['title'],getStoreId(),$detail);
        }
        return jsonResponse([
            'msg'=>'ok'
        ],200);
    }
    public function editProductCategory(Request $post)
    {
        $id = $post->id?$post->id:0;
        $title = $post->title;
        $details = $post->detailArray;
        if ($this->handle->editProductCategory($id,$title)) {
            if (!empty($details)){
//                dd($details);
                foreach ($details as $detail){
                    $this->handle->editCategoryDetail($detail['id'],$detail['title'],$id);
                }
            }
        }
        return jsonResponse([
            'msg'=>'ok'
        ],200);
    }
    public function delProductCategory()
    {
        $id = Input::get('id');
        if ($this->handle->delProductCategory($id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'参数错误！'
        ],400);
    }
    public function getProductCategories()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $title = Input::get('title','');
        $data = $this->handle->getProductCategories(getStoreId(),$page,$limit,$title);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function addProduct(Request $post)
    {
        $id = $post->id?$post->id:0;
        $data = [
            'store_id'=>getStoreId(),
            'name'=>$post->name,
            'detail'=>$post->detail,
            'brokerage'=>$post->brokerage,
            'express'=>0,
            'express_price'=>0,
            'share_title'=>$post->share_title,
            'share_detail'=>$post->share_detail,
            'type_id'=>$post->type_id,
            'norm'=>$post->norm,
        ];
        $stock = $post->stock;
        $norm = $post->norm;
        $delStocks = $post->delStocks?$post->delStocks:null;
//        dd($data);
        $product_id = $this->handle->addProduct($id,$data);
        if ($product_id){
            foreach ($stock as $item){
                if ($norm!='fixed'){
                    $swap = [];
                    $modifyBool = true;
                    if (isset($item['product_detail'])){
                        $detail = $item['product_detail'];
                        $modifyBool = false;
                    }
                    if ($modifyBool){
                        if (isset($item['detail'])){
                            foreach ($item['detail'] as $detail){
                                $detail_id = $this->handle->addProductCategorySnapshot($product_id,$detail);
                                array_push($swap,$detail_id);
                            }
                            sort($swap);
                            $detail = implode(',',$swap);
                        }
                    }

                }else{
                    $detail = 'fixed';
                }
                $stockData = [
                    'product_id'=>$product_id,
                    'cover'=>$item['cover'],
                    'price'=>$item['price'],
                    'origin_price'=>$item['origin_price'],
                    'product_detail'=>is_array($detail)?implode(',',$detail):$detail
                ];
                $images = $item['images'];
                $stockId = isset($item['id'])&&$item!=0?$item['id']:0;
//                $this->handle->delStocks($product_id);
                $stock_id = $this->handle->addStock($stockId,$stockData);
                $this->handle->delStockImages($stock_id);
                foreach ($images as $image){
                    $this->handle->addStockImage($stock_id,$image);
                }
            }
            if (!empty($delStocks)){
                $this->handle->delStocksByIdArray($delStocks);
            }
        }
        return jsonResponse([
            'msg'=>'ok'
        ]);
    }
    public function getProducts()
    {
        $state = Input::get('state');
        $name = Input::get('name');
        $deleted = Input::get('deleted',1);
        if ($name){
            $storeId = $this->handle->getStoresId($name);
            $type_id = $this->handle->getProductTypesId($name);
        }else{
            $storeId = null;
            $type_id = null;
        }

        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        if (checkPermission(Auth::id(),'productListAll')) {
            $data = $this->handle->getProducts($storeId,$type_id,$page,$limit,$name,0,$state,$deleted);
        }else{
            $data = $this->handle->getProducts([getStoreId()],$type_id,$page,$limit,$name,0,$state,$deleted);
        }
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function getProductsByType()
    {
        $type = Input::get('type');
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        if (checkPermission(Auth::id(),'productListAll')) {
            $data = $this->handle->getProducts(0,[$type],$page,$limit,'',2,2,1);
        }else{
            $data = $this->handle->getProducts([getStoreId()],[$type],$page,$limit,'',2,2,1);
        }
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function delProduct()
    {
        $id = Input::get('id',0);
        if ($this->handle->delProduct($id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            '操作失败！'
        ]);
    }
    public function softDelProduct()
    {
        $id = Input::get('id',0);
        $product = $this->handle->getProductById($id);
        if ($product->deleted==1) {
            $data = [
                'deleted' => 0
            ];
            if ($this->handle->addProduct($id,$data)){
                return jsonResponse([
                    'msg'=>'ok'
                ]);
            }
        }
        if ($this->handle->softDelProduct($id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            '操作失败！'
        ]);
    }
    public function getProductsApi()
    {
        $name = Input::get('name');
        $type = Input::get('type');
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $data = $this->handle->getProductsApi($name,$type,$page,$limit);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function getProduct()
    {
        $id = Input::get('id');
        $data = $this->handle->getProduct($id);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function getProductApi()
    {
        $id = Input::get('id');
        $product = $this->handle->getProduct($id);
        $user_id = getRedisData(Input::get('token'))?getRedisData(Input::get('token')):0;
        $product->collect = $this->handle->checkCollect($user_id,$product->id);
        $product->express = $this->handle->getStoreExpress($product->store_id);
        $config = $this->handle->getDiscountConfig();
        if (!empty($config)&&$config->state==1){
            switch ($config->type){
                case 1:
                    $product->discount = $config;
                    break;
                case 2:
                    $items = $this->handle->getDisCountItems();
                    if (in_array($product->type_id,$items)){
                        $product->discount = $config;
                    }
                    break;
                case 3:
                    $items = $this->handle->getDisCountItems();
                    if (in_array($product->store_id,$items)){
                        $product->discount = $config;
                    }
                    break;
                case 4:
                    $items = $this->handle->getDisCountItems();
                    if (in_array($product->id,$items)){
                        $product->discount = $config;
                    }
                    break;
            }
        }
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$product
        ]);
    }
    public function getProductAssesses()
    {
        $product_id = Input::get('product_id');
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $data = $this->handle->getProductAssesses($product_id,$page,$limit);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function checkProduct()
    {
        $id = Input::get('id');
        $product = $this->handle->getProductById($id);
        $review = $product->review==0?1:0;
        $data = [
            'review'=>$review
        ];
        if ($this->handle->addProduct($id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'操作错误！'
        ],400);
    }
    public function shelfProduct()
    {
        $id = Input::get('id');
        $product = $this->handle->getProductById($id);
        $state = $product->state==0?1:0;
        $data = [
            'state'=>$state
        ];
        if ($this->handle->addProduct($id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'操作错误！'
        ],400);
    }
    public function getStock()
    {
        $product_id = Input::get('product_id');
        $detail = Input::get('detail');
        if ($detail){
            $detail = explode(',',$detail);
            sort($detail);
            $detail = array_filter($detail,function ($item){
                return $item!='';
            });
            $detail = implode(',',$detail);
        }
        $stock = $this->handle->getStock($product_id,$detail);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$stock
        ]);
    }
    public function addCart(Request $post)
    {
        $user = getRedisData($post->token);
        $stock = $this->handle->getStockById($post->stock_id);
        $product = $this->handle->getProductById($stock->product_id);
        if ($this->handle->addCart($user,$stock->id,$product->store_id,$post->number)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        };
        return jsonResponse([
            'msg'=>'操作失败！'
        ],400);
    }
    public function delCarts()
    {
        $id = Input::get('id');
        $id = explode(',',$id);
        if ($this->handle->delCarts($id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }

    }
    public function getCarts()
    {
        $user_id = getRedisData(Input::get('token'));
        $carts = $this->handle->getCarts($user_id);
        $carts = $this->handle->formatCarts($carts);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$carts
        ]);
    }
    public function addCollect()
    {
        $product_id = Input::get('product_id');
        $user_id = getRedisData(Input::get('token'));
        $count = $this->handle->checkCollect($user_id,$product_id);
        if ($count!=0){
            return jsonResponse([
                'msg'=>'已收藏该商品！'
            ],400);
        }
        if ($this->handle->addCollect($user_id,$product_id)){
            return jsonResponse(['msg'=>'收藏成功！']);
        }
        return jsonResponse(['msg'=>'操作失败！'],400);
    }
    public function getCollects()
    {
        $user_id = getRedisData(Input::get('token'));
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $collects = $this->handle->getUserCollect($user_id,$page,$limit);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$collects
        ]);
    }
    public function delCollect()
    {
        $id = Input::get('id');
        $product_id = Input::get('product_id');
        $user_id = getRedisData(Input::get('token'));
        if ($user_id){
            if ($this->handle->delCollectByProductId($user_id,$product_id)){
                return jsonResponse([
                    'msg'=>'ok'
                ]);
            }
            return jsonResponse([
                'msg'=>'操作失败！'
            ]);
        }
        if ($this->handle->delCollect($id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'操作失败！'
        ]);
    }
    public function getProductQrCode()
    {
        $project_id = Input::get('project_id');
        $wx =  getWxXcx();
        $data = [
            'scene'=>"project_id=" . $project_id,
            'page'=>"pages/goods/detail/detail"
        ];
        $data = json_encode($data);
        $token = $wx->getAccessToken();
        $qrcode = $wx->get_http_array('https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$token['access_token'],$data,'json');
        return response()->make($qrcode,200,['content-type'=>'image/gif']);
    }
    public function addHot()
    {
        $product_id = Input::get('product_id');
        if ($this->handle->addHot($product_id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ]);
    }
    public function addNew()
    {
        $product_id = Input::get('product_id');
        if ($this->handle->addNew($product_id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ]);
    }
    public function addOffer()
    {
        $product_id = Input::get('product_id');
        $sort = Input::get('sort',0);
        if ($this->handle->addOffer($product_id,$sort)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ]);
    }
    public function getRecommendList()
    {
        $type = Input::get('type');
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        switch ($type){
            case 'hot':
                $data = $this->handle->getHotList($page,$limit);
                break;
            case 'new':
                $data = $this->handle->getNewList($page,$limit);
                break;
            case 'offer':
                $data = $this->handle->getOfferList($page,$limit);
                break;
            default:
                $data = $this->handle->getHotList($page,$limit);
                break;
        }
        $this->handle->formatRecommendList($data['data']);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function addHotType()
    {
        $type_id = Input::get('type_id');
        if ($this->handle->addHotType($type_id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'已超出热门分类数量限制！'
        ],400);
    }
    public function getHotTypes()
    {
        $data = $this->handle->getHotTypes();
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function addNotifyQueue()
    {
        $id = Input::get('id');
        $product = $this->handle->getProductById($id);
        $stock = $this->handle->getStockByProductId($id);
        $lists = $this->handle->getNotifyList();
        $store = $this->handle->getStoreById($product->store_id);
        $name = Input::get('name','');
        $intro = Input::get('intro','');
        if (!empty($lists)){
            foreach ($lists as $list){
                $data = [
                    "touser"=>$list->open_id,
                    "template_id"=>$this->handle->getNotifyConfigByTitle('product_notify'),
                    "form_id"=>$list->notify_id,
                    "page"=>"pages/goods/detail/detail?id=".$id,
                    "data"=>[
                        "keyword1"=>[
                            "value"=>$name
                        ],
                        "keyword2"=>[
                            "value"=>$store->name
                        ],
                        "keyword3"=>[
                            "value"=>date('Y-m-d H:i:s',strtotime($product->created_at))
                        ],
                        "keyword4"=>[
                            "value"=>number_format($stock->origin_price,2)
                        ],
                        "keyword5"=>[
                            "value"=>number_format($stock->price,2)
                        ],
                        "keyword6"=>[
                            "value"=>$intro
                        ]
                    ]
                ];
                $this->handle->addNotifyQueue(json_encode($data));
                $this->handle->delNotifyList($list->id);
            }
        }
        return jsonResponse([
            'msg'=>'ok'
        ]);
    }

}
