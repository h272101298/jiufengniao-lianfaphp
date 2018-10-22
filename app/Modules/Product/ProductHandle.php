<?php
/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/6/23
 * Time: 下午4:23
 */

namespace App\Modules\Product;


use App\Modules\Discount\Model\DiscountConfig;
use App\Modules\Discount\Model\DiscountItem;
use App\Modules\Order\Model\Order;
use App\Modules\Order\Model\StockSnapshot;
use App\Modules\Product\Model\Cart;
use App\Modules\Product\Model\CategoryDetail;
use App\Modules\Product\Model\HotList;
use App\Modules\Product\Model\HotTypeList;
use App\Modules\Product\Model\NewList;
use App\Modules\Product\Model\OfferList;
use App\Modules\Product\Model\Product;
use App\Modules\Product\Model\ProductCategory;
use App\Modules\Product\Model\ProductCategorySnapshot;
use App\Modules\Product\Model\ProductDetailSnapshot;
use App\Modules\Product\Model\ProductType;
use App\Modules\Product\Model\ProductTypeBind;
use App\Modules\Product\Model\Stock;
use App\Modules\Product\Model\StockImage;
use App\Modules\Store\Model\Store;
use App\Modules\Store\Model\StoreExpress;
use App\Modules\WeChatUser\Model\ProductCollect;
use App\Modules\WeChatUser\Model\WeChatUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait ProductHandle
{
    public function addProductType($id = 0, $data, $parent = 0)
    {
        if ($id != 0 && $id == $parent) {
            return false;
        }
        if ($id) {
            $type = ProductType::find($id);
        } else {
            $type = new ProductType();
        }
        foreach ($data as $key => $value) {
            $type->$key = $value;
        }
        if ($type->save()) {
            ProductTypeBind::where('type_id', '=', $type->id)->delete();
            $bind = new ProductTypeBind();
            $bind->type_id = $type->id;
            $bind->parent_id = $parent;
            $bind->save();
            return true;
        }
        return false;
    }

    public function delProductType($id)
    {
        $type = ProductType::findOrFail($id);
        if ($type->delete()) {
            ProductTypeBind::where('type_id', '=', $id)->delete();
            return true;
        }
        return false;
    }

    public function delHotType($type_id)
    {
        return HotTypeList::where('type_id', '=', $type_id)->delete();
    }

    public function getProductTypesId($name)
    {
        $dbObj = DB::table('product_types');
        if ($name) {
            $dbObj->where('title', 'like', '%' . $name . '%');
        }
        return $dbObj->pluck('id')->toArray();
    }

    public function getProductTypes($page = 1, $limit = 10, $title = '', $level = 0, $parent = 0, $store_id = 0)
    {
        $dbObj = DB::table('product_types');
        if ($store_id) {

        }
        if ($title) {
//            dd($title);
            $dbObj->where('title', 'like', '%' . $title . '%');
        }
        if ($parent) {
            $idArr = ProductTypeBind::where('parent_id', '=', $parent)->pluck('type_id')->toArray();
            $dbObj->whereIn('id', $idArr);
        }
        if ($level) {
            switch ($level) {
                case 1:
                    $idArr = ProductTypeBind::where('parent_id', '=', 0)->pluck('type_id')->toArray();
                    break;
                case 2:
                    $swap = ProductTypeBind::where('parent_id', '=', 0)->pluck('type_id')->toArray();
                    $idArr = ProductTypeBind::whereIn('parent_id', $swap)->pluck('type_id')->toArray();
                    break;
                case 3:
                    $swap = ProductTypeBind::where('parent_id', '=', 0)->pluck('type_id')->toArray();
                    $swap = ProductTypeBind::whereIn('parent_id', $swap)->pluck('type_id')->toArray();
                    $idArr = ProductTypeBind::whereIn('parent_id', $swap)->pluck('type_id')->toArray();
                    break;
            }
            $dbObj->whereIn('id', $idArr);
        }
        $count = $dbObj->count();
        $types = $dbObj->limit($limit)->offset(($page - 1) * $limit)->get();
        return [
            'data' => $types,
            'count' => $count
        ];
    }
    public function countTypeProducts($type_id)
    {
        return Product::where('type_id','=',$type_id)->count();
    }
    public function checkTypeChild($type_id)
    {
        return ProductTypeBind::where('parent_id','=',$type_id)->count();
    }
    public function getProductTypesParents()
    {
        $idArray = ProductTypeBind::where('parent_id', '=', 0)->pluck('type_id')->toArray();
        $data = ProductType::whereIn('id',$idArray)->get()->toArray();
        return $data;
    }
    public function getProductTypesTreeByParent($parent)
    {
//        $level1 = ProductTypeBind::where('parent_id', '=', 0)->pluck('type_id')->toArray();
        $level2 = ProductTypeBind::where('parent_id','=', $parent)->pluck('type_id')->toArray();
        $level3 = ProductTypeBind::whereIn('parent_id', $level2)->pluck('type_id')->toArray();
        $data = ProductType::all()->toArray();
        $bind = ProductTypeBind::all()->toArray();
        $bind = array_column($bind, 'parent_id', 'type_id');
        for ($i = 0; $i < count($data); $i++) {
            $data[$i]['parent_id'] = $bind[$data[$i]['id']];
        }
        $level2 = array_filter($data, function ($item) use ($level2) {
            return in_array($item['id'], $level2);
        });
        $level2 = array_merge($level2);
        $level3 = array_filter($data, function ($item) use ($level3) {
            return in_array($item['id'], $level3);
        });
        $level3 = array_merge($level3);
        for ($i = 0; $i < count($level2); $i++) {
            $id = $level2[$i]['id'];
            $level2[$i]['child'] = array_filter($level3, function ($item) use ($id) {
                return $item['parent_id'] == $id;
            });
        }
        return $level2;
    }
    public function getProductTypesTree()
    {
        $level1 = ProductTypeBind::where('parent_id', '=', 0)->pluck('type_id')->toArray();
        $level2 = ProductTypeBind::whereIn('parent_id', $level1)->pluck('type_id')->toArray();
        $level3 = ProductTypeBind::whereIn('parent_id', $level2)->pluck('type_id')->toArray();
        $data = ProductType::all()->toArray();
        $bind = ProductTypeBind::all()->toArray();
        $bind = array_column($bind, 'parent_id', 'type_id');
        for ($i = 0; $i < count($data); $i++) {
            $data[$i]['parent_id'] = $bind[$data[$i]['id']];
        }
//        dd($data);
        $level1 = array_filter($data, function ($item) use ($level1) {
            return in_array($item['id'], $level1);
        });
        $level1 = array_merge($level1);
        $level2 = array_filter($data, function ($item) use ($level2) {
            return in_array($item['id'], $level2);
        });
        $level2 = array_merge($level2);
        $level3 = array_filter($data, function ($item) use ($level3) {
            return in_array($item['id'], $level3);
        });
        $level3 = array_merge($level3);
        for ($i = 0; $i < count($level2); $i++) {
            $id = $level2[$i]['id'];
            $level2[$i]['child'] = array_filter($level3, function ($item) use ($id) {
                return $item['parent_id'] == $id;
            });
        }
        for ($i = 0; $i < count($level1); $i++) {
            $id = $level1[$i]['id'];
            $level1[$i]['child'] = array_filter($level2, function ($item) use ($id) {
                return $item['parent_id'] == $id;
            });
        }
        return $level1;
    }

    public function formatProductTypes(&$types)
    {
        if (empty($types)) {
            return [];
        }
        foreach ($types as $type) {
            $type->is_hot = HotTypeList::where('type_id', '=', $type->id)->count();
            $bind = ProductTypeBind::where('type_id', '=', $type->id)->first();
            if ($bind->parent_id == 0) {
                $type->level = 1;
                $type->parent = null;
            } else {
                $type->level = 2;
                $swap = ProductTypeBind::where('type_id', '=', $bind->parent_id)->first();
                $type->parent = ProductType::find($bind->parent_id);
                if (!empty($swap) && $swap->parent_id != 0) {
                    $type->level = 3;
                }
            }
        }
        return $types;
    }

    public function addHotType($type_id)
    {
        $count = HotTypeList::where('type_id', '!=', $type_id)->count();
        if ($count >= 5) {
            return false;
        }
        $hot = HotTypeList::where('type_id', '=', $type_id)->first();
        if (empty($hot)) {
            $hot = new HotTypeList();
            $hot->type_id = $type_id;
            $hot->save();
            return true;
        }
        if ($hot->delete()) {
            return true;
        }
        return false;
    }

    public function getHotTypes()
    {
        $type_id = HotTypeList::pluck('type_id')->toArray();
        $type = ProductType::whereIn('id', $type_id)->get();
        return $type;
    }

    public function addProductCategory($id = 0, $type_id, $title, $store_id = 0, $detail = [])
    {
        if ($id) {
            $category = ProductCategory::find($id);
        } else {
            $category = new ProductCategory();
        }
        $category->title = $title;
        $category->store_id = $store_id;
        $category->type_id = $type_id;
        if ($category->save()) {
            CategoryDetail::where('category_id', '=', $category->id)->delete();
            foreach ($detail as $item) {
                $detail = new CategoryDetail();
                $detail->category_id = $category->id;
                $detail->title = $item;
                $detail->save();
            }
            return true;
        }
        return false;
    }
    public function editProductCategory($id,$title)
    {
        $category = $id?ProductCategory::find($id):new ProductCategory();
        $category->title = $title;
        if ($category->save()){
            return true;
        }
        return false;
    }
    public function editCategoryDetail($id,$title,$category_id=0)
    {
        if ($id) {
            $detail = CategoryDetail::find($id);
        } else {
            $detail = new CategoryDetail();
            $detail->category_id = $category_id;
        }
        $detail->title = $title;
        if ($detail->save()){
            return true;
        }
        return false;
    }

    public function getProductCategories($store_id = 0, $page = 1, $limit = 10, $title = '')
    {
        $db = DB::table('product_categories');
        if ($store_id){
            $db->where('store_id', '=', $store_id);
        }
        if ($title) {
            $db->where('title', 'like', '%' . $title . '%');
        }
        $count = $db->count();
        $data = $db->limit($limit)->offset(($page - 1) * $limit)->get();
        if (!empty($data)) {
            $this->formatProductCategory($data);
        }
        return [
            'data' => $data,
            'count' => $count
        ];
    }

    public function delProductCategory($id)
    {
        $category = ProductCategory::findOrFail($id);
        if ($category->delete()) {
            CategoryDetail::where('category_id', '=', $id)->delete();
            return true;
        }
        return false;
    }

    public function formatProductCategory(&$data)
    {
        foreach ($data as $datum) {
            $array = CategoryDetail::where('category_id', '=', $datum->id)->get()->toArray();
            $detail = array_column($array, 'title');
            $datum->detail = implode(',', $detail);
            $datum->detailArray = $array;
        }
        return $data;
    }

    public function addProductCategorySnapshot($product_id, $detail_id)
    {
        $detail = CategoryDetail::findOrFail($detail_id);
        $category = ProductCategory::findOrFail($detail->category_id);
        $categorySnap = ProductCategorySnapshot::where('product_id', '=', $product_id)->where('title', '=', $category->title)->first();
        if (empty($categorySnap)) {
            $categorySnap = new ProductCategorySnapshot();
            $categorySnap->product_id = $product_id;
            $categorySnap->title = $category->title;
            $categorySnap->save();
        }
        $detailSnap = ProductDetailSnapshot::where('category_id', '=', $categorySnap->id)->where('title', '=', $detail->title)->first();
        if (empty($detailSnap)) {
            $detailSnap = new ProductDetailSnapshot();
            $detailSnap->category_id = $categorySnap->id;
            $detailSnap->title = $detail->title;
            $detailSnap->save();
        }
        return $detailSnap->id;
    }

    public function addProduct($id = 0, $data)
    {
        if ($id) {
            $product = Product::find($id);
        } else {
            $product = new Product();
        }
        foreach ($data as $key => $value) {
            $product->$key = $value;
        }
        if ($product->save()) {
            return $product->id;
        }
        return false;
    }

    public function delProduct($id)
    {
        $product = Product::findOrFail($id);
        if ($product->delete()) {
            $categoryId = ProductCategorySnapshot::where('product_id', '=', $id)->pluck('id')->toArray();
            ProductDetailSnapshot::whereIn('category_id', $categoryId)->delete();
            ProductCategorySnapshot::where('product_id', '=', $id)->delete();
            $stockId = Stock::where('product_id', '=', $id)->pluck('id')->toArray();
            StockImage::whereIn('stock_id', $stockId)->delete();
            Stock::where('product_id', '=', $id)->delete();
            HotList::where('product_id', '=', $id)->delete();
            OfferList::where('product_id', '=', $id)->delete();
            NewList::where('product_id', '=', $id)->delete();
            return true;
        }
        return false;
    }

    public function softDelProduct($id)
    {
        $product = Product::findOrFail($id);
        $product->deleted = 1;
        if ($product->save()) {
            return true;
        }
        return false;
    }

    public function getProducts($store_id = null, $type_id = null, $page = 1, $limit = 100, $name = '', $review = 0, $state = 0, $deleted = 0, $idArr = null)
    {
        $db = DB::table('products');
        if ($store_id) {
            $db->whereIn('store_id', $store_id);
        }
        if ($type_id) {
            $db->whereIn('type_id', $type_id);
        }
        if ($idArr) {
            $db->whereIn('id', $idArr);
        }
        if ($name) {
            $db->where('name', 'like', '%' . $name . '%');
        }
        if ($review) {
            $db->where('review', '=', $review - 1);
        }
        if ($state) {
            $db->where('state', '=', $state - 1);
        }
        if ($deleted) {
            $db->where('deleted', '=', $deleted - 1);
        }
//        $bindings = $db->getBindings();
//        $sql = str_replace('?', '%s', $db->toSql());
//        $sql = sprintf($sql, ...$bindings);
//        dd($sql);
        $count = $db->count();
        $data = $db->limit($limit)->offset(($page - 1) * $limit)->get();
//        var_dump($data);
        $this->formatProducts($data);
        return [
            'data' => $data,
            'count' => $count
        ];
    }

    public function formatProducts(&$data)
    {
        if (empty($data)) {
            return [];
        }
        foreach ($data as $item) {
            unset($item->detail);
            $item->cover = Stock::where('product_id', '=', $item->id)->pluck('cover')->first();
            $item->hot = HotList::where('product_id', '=', $item->id)->count();
            $item->new = NewList::where('product_id', '=', $item->id)->count();
            $item->offer = OfferList::where('product_id', '=', $item->id)->count();
            $item->offerData = OfferList::where('product_id', '=', $item->id)->first();
        }
        return $data;
    }

    public function getStoreProducts($store_id, $page = 1, $limit = 100, $name, $review = 0, $state = 0, $idArr)
    {
        $db = DB::table('products')->where('store_id', '=', $store_id)->where('deleted', '!=', 1);
        if ($idArr) {
            $db->whereIn('id', $idArr);
        }
        if ($name) {
            $db->where('name', 'like', '%' . $name . '%');
        }
        if ($review) {
            $db->where('review', '=', $review - 1);
        }
        if ($state) {
            $db->where('state', '=', $state - 1);
        }
        $count = $db->count();
        $data = $db->limit($limit)->offset(($page - 1) * $limit)->get();
        return [
            'data' => $data,
            'count' => $count
        ];
    }

    public function getProductsApi($name, $type,$page=1,$limit=10)
    {
        $db = DB::table('products')->where('deleted', '!=', 1)->where('state', '=', 1)->where('review', '=', 1);
        if ($name) {
            $db->where('name', 'like', '%' . $name . '%');
        }
        if ($type) {
            $db->where('type_id', '=', $type);
        }
        $db->where('review', '=', 1)->where('state', '=', 1)->where('deleted', '=', 0);
        $count = $db->count();
        $data = $db->select(['name', 'id', 'norm','sales_volume'])->limit($limit)->offset(($page-1)*$limit)->get();
        $data = $this->formatProductApi($data);
        return [
            'data' => $data,
            'count' => $count
        ];
    }

    public function formatProductApi(&$products)
    {
        if (empty($products)) {
            return [];
        }
        foreach ($products as $product) {
            if ($product->norm == 'fixed') {
                $stock = Stock::where('product_id', '=', $product->id)->first();
                $product->cover = $stock->cover;
                $product->price = $stock->price;
                $product->origin_price = $stock->origin_price;
            } else {
                $stock = Stock::where('product_id', '=', $product->id)->orderBy('price', 'ASC')->first();
                $product->cover = $stock->cover;
                $product->price = $stock->price;
                $product->origin_price = $stock->origin_price;
            }
        }
        return $products;
    }

    public function getProduct($id)
    {
        $product = Product::find($id);
        $product->store = Store::find($product->store_id);
        $type = [];
        $type_id = $product->type_id;
        do {
            array_push($type, $type_id);
            $swap = ProductTypeBind::where('type_id', '=', $type_id)->first();
            if (!empty($swap)){
                $type_id = $swap->parent_id;
            }
        } while ($type_id != 0);
        $product->typeArray = array_reverse($type);
        $stocks = Stock::where('product_id', '=', $product->id)->orderBy('price', 'ASC')->get();
        if (!empty($stocks)) {
            foreach ($stocks as $stock) {
                if ($stock->detail!='fixed'){
                    $detail = explode(',',$stock->product_detail);
                    $detailData = ProductDetailSnapshot::select(['id','title'])->whereIn('id',$detail)->get()->toArray();
                    $stock->product_detail = array_column($detailData,'id');
                    $stock->detail_title = array_column($detailData,'title');
                    $stock->images = StockImage::where('stock_id', '=', $stock->id)->pluck('url')->toArray();
                }
            }
        }
        $product->default = $stocks[0];
        $product->stocks = $stocks;
        $categories = ProductCategorySnapshot::where('product_id', '=', $product->id)->get();
        if (!empty($categories)) {
            foreach ($categories as $category) {
                $category->detail = ProductDetailSnapshot::where('category_id', '=', $category->id)->get();
            }
        }
        $product->categories = $categories;
        return $product;
    }

    public function getProductAssesses($product, $page, $limit)
    {
        $db = DB::table('stock_snapshots')->where('product_id', '=', $product)->where('is_assess', '=', 1);
        $count = $db->count();
        $data = $db->limit($limit)->offset(($page - 1) * $limit)->get();
        if (!empty($data)) {
            foreach ($data as $item) {
                $user_id = Order::find($item->order_id)->user_id;
                $item->user = WeChatUser::find($user_id);
            }
        }
        return [
            'data' => $data,
            'count' => $count
        ];
    }

    public function getProductById($id)
    {
        $product = Product::findOrFail($id);
        return $product;
    }

    public function delStocks($product_id)
    {
        return Stock::where('product_id', '=', $product_id)->delete();
    }

    public function addStock($id, $data)
    {
        if ($id) {
            $stock = Stock::find($id);
        } else {
            $stock = new Stock();
        }
        foreach ($data as $key => $value) {
            $stock->$key = $value;
        }
        if ($stock->save()) {
            return $stock->id;
        }
        return false;
    }
    public function delStocksByIdArray($idArray){
        StockImage::whereIn('stock_id',$idArray)->delete();
        Stock::whereIn('id',$idArray)->delete();
        return true;
    }

    public function addStockImage($id, $url)
    {
        $image = new StockImage();
        $image->stock_id = $id;
        $image->url = $url;
        if ($image->save()) {
            return true;
        }
        return false;
    }
    public function delStockImages($stock_id)
    {
        return StockImage::where('stock_id','=',$stock_id)->delete();
    }
    public function getStock($product, $detail)
    {
        $stock = Stock::where('product_id', '=', $product)->where('product_detail', '=', $detail)->first();
        if (!empty($stock)) {
            $stock->images = StockImage::where('stock_id', '=', $stock->id)->pluck('url')->toArray();
        }
        return $stock;
    }

    public function getStockById($id)
    {
        $stock = Stock::findOrFail($id);
        return $stock;
    }

    public function getStockByProductId($product_id)
    {
        $stock = Stock::where('product_id', '=', $product_id)->orderBy('price', 'ASC')->first();
        return $stock;
    }

    public function getStocksByProductId($product_id)
    {
        $stock = Stock::where('product_id', '=', $product_id)->orderBy('price', 'ASC')->get();
        return $stock;
    }

    public function formatStocks(&$stocks)
    {
        if (empty($stocks)) {
            return [];
        }
        foreach ($stocks as $stock) {
            if ($stock->product_detail != 'fixed') {
                $idArray = explode(',', $stock->product_detail);
                $detailArray = ProductDetailSnapshot::whereIn('id', $idArray)->pluck('title')->toArray();
                $stock->detail = implode(',', $detailArray);
            }

        }
        return $stocks;
    }

    public function addCart($uid, $stock_id, $store_id, $number)
    {
        $cart = Cart::where('user_id', '=', $uid)->where('stock_id', '=', $stock_id)->where('store_id', '=', $store_id)->first();
        if (empty($cart)) {
            $cart = new Cart();
        }
        $cart->user_id = $uid;
        $cart->stock_id = $stock_id;
        $cart->store_id = $store_id;
        $cart->number = $number;
        if ($cart->save()) {
            return true;
        }
        return false;
    }

    public function getCarts($user_id)
    {
        $carts = Cart::where('user_id', '=', $user_id)->get()->toArray();
        return $carts;
    }

    public function formatCarts($carts)
    {
        $data = [];
        if (empty($carts)) {
            return $data;
        }
        $store = array_column($carts, 'store_id');
        $store = array_merge(array_unique($store));
        for ($i = 0; $i < count($store); $i++) {
            $data[$i]['shopname'] = Store::find($store[$i])->name;
            $data[$i]['shopid'] = $store[$i];
            $express = StoreExpress::where('store_id','=',$store[$i])->first();
            $data[$i]['express'] = empty($express)?0:$express->price;
            $store_id = $store[$i];
            $swapCarts = array_filter($carts, function ($item) use ($store_id) {
                return $item['store_id'] == $store_id;
            });
            $swap = [];
            if (!empty($swapCarts)) {
                foreach ($swapCarts as $swapCart) {
                    $stock = Stock::find($swapCart['stock_id']);
                    if (!empty($stock)) {
                        $product = Product::find($stock->product_id);
                        $config = DiscountConfig::first();
                        if (!empty($config)&&$config->state==1){
                            $items = DiscountItem::pluck('item')->toArray();
                            switch ($config->type){
                                case 1:
                                    $swapCart['discount'] = $config;
                                    break;
                                case 2:
                                    //$items = $this->getDisCountItems();
                                    if (in_array($product->type_id,$items)){
                                        $swapCart['discount'] = $config;
                                    }
                                    break;
                                case 3:
                                   // $items = $this->getDisCountItems();
                                    if (in_array($product->store_id,$items)){
                                        $swapCart['discount'] = $config;
                                    }
                                    break;
                                case 4:
                                   // $items = $this->getDisCountItems();
                                    if (in_array($product->id,$items)){
                                        $swapCart['discount'] = $config;
                                    }
                                    break;
                            }
                        }
                        $swapCart['goodid'] = $swapCart['stock_id'];
                        $swapCart['shopid'] = $store[$i];
                        $swapCart['goodname'] = $product->name;
                        $swapCart['goodpic'] = $stock->cover;
                        $swapCart['goodprice'] = sprintf('%.2f', $stock->price);
                        $swapCart['goodnum'] = $swapCart['number'];
                        if ($product->norm == 'fixed') {
                            $swapCart['goodformat'] = 'fixed';
                        } else {
                            $detail = explode(',', $stock->product_detail);
                            $detail = ProductDetailSnapshot::whereIn('id', $detail)->pluck('title')->toArray();
                            $detail = implode(' ', $detail);
                            $swapCart['goodformat'] = $detail;
                        }
                        $swapCart['enable'] = 1;
                        array_push($swap, $swapCart);
                    } else {
                        $swapCart['goodid'] = 0;
                        $swapCart['shopid'] = $store[$i];
                        $swapCart['goodname'] = '已下架';
                        $swapCart['goodpic'] = '已下架';
                        $swapCart['goodprice'] = sprintf('%.2f', 0);
                        $swapCart['goodnum'] = $swapCart['number'];
                        $swapCart['goodformat'] = 'fixed';
                        $swapCart['enable'] = 0;
                        array_push($swap, $swapCart);
                    }
                }
            }
            $data[$i]['goods'] = $swap;
        }
        return $data;
    }

    public function delUserCart($user_id)
    {
        Cart::where('user_id', '=', $user_id)->delete();
        return true;
    }

    public function delCarts($idArray)
    {
        Cart::whereIn('id', $idArray)->delete();
        return true;
    }

    public function addCollect($user_id, $product_id)
    {
        $product = Product::findOrFail($product_id);
        if (empty($product)) {
            return false;
        }
        $stock = Stock::where('product_id', '=', $product_id)->orderBy('price', 'ASC')->first();
        $collect = new ProductCollect();
        $collect->user_id = $user_id;
        $collect->product_id = $product_id;
        $collect->price = $stock->price;
        $collect->origin_price = $stock->origin_price;
        $collect->cover = $stock->cover;
        $collect->name = $product->name;
        if ($collect->save()) {
            return true;
        }
        return false;
    }

    public function getUserCollect($user, $page = 1, $limit = 10)
    {
        $count = ProductCollect::where('user_id', '=', $user)->count();
        $collects = ProductCollect::where('user_id', '=', $user)->limit($limit)->offset(($page - 1) * $limit)->orderBy('id', 'DESC')->get();
        return [
            'data' => $collects,
            'count' => $count
        ];
    }

    public function checkCollect($user_id, $product_id)
    {
        $count = ProductCollect::where('user_id', '=', $user_id)->where('product_id', '=', $product_id)->count();
        return $count;
    }

    public function delCollect($id)
    {
        $collect = ProductCollect::findOrFail($id);
        if ($collect->delete()) {
            return true;
        }
        return false;
    }
    public function delCollectByProductId($user_id,$product_id)
    {
        $collect = ProductCollect::where('user_id', '=', $user_id)->where('product_id', '=', $product_id)->first();
        if (empty($collect)){
            return true;
        }
        if ($collect->delete()){
            return true;
        }
        return false;
    }

    public function formatCollect(&$collect)
    {
        if (empty($collect)) {
            return [];
        }
        for ($i = 0; $i < count($collect); $i++) {
            $product = Product::find($collect[$i]->product_id);
            $collect[$i]->product = $product ? $product : [];
            $collect[$i]->cover = Stock::where('product_id', '=', $product->id)->pluck('cover')->first();
        }
        return $collect;
    }

    public function addHot($product_id)
    {
        $hot = HotList::where('product_id', '=', $product_id)->first();
        if (empty($hot)) {
            $hot = new HotList();
            $hot->product_id = $product_id;
            $hot->save();
            return true;
        }
        if ($hot->delete()) {
            return true;
        }
        return false;
    }

    public function addNew($product_id)
    {
        $new = NewList::where('product_id', '=', $product_id)->first();
        if (empty($new)) {
            $new = new NewList();
            $new->product_id = $product_id;
            $new->save();
            return true;
        }
        if ($new->delete()) {
            return true;
        }
        return false;
    }

    public function addOffer($product_id,$sort)
    {
        $offer = OfferList::where('product_id', '=', $product_id)->first();
        if (empty($offer)) {
            $offer = new OfferList();
            $offer->product_id = $product_id;
            $offer->sort = $sort;
            $offer->save();
            return true;
        }
        if ($offer->delete()) {
            return true;
        }
        return false;
    }

    public function getHotList($page = 1, $limit = 10)
    {
        $count = HotList::count();
        $list = HotList::orderBy('id','DESC')->limit($limit)->offset(($page - 1) * $limit)->get();
        return [
            'data' => $list,
            'count' => $count
        ];
    }

    public function getNewList($page = 1, $limit = 10)
    {
        $count = NewList::count();
        $list = NewList::orderBy('id','DESC')->limit($limit)->offset(($page - 1) * $limit)->get();
        return [
            'data' => $list,
            'count' => $count
        ];
    }

    public function getOfferList($page = 1, $limit = 10)
    {
        $count = OfferList::count();
        $list = OfferList::limit($limit)->offset(($page - 1) * $limit)->orderBy('sort','DESC')->get();
        return [
            'data' => $list,
            'count' => $count
        ];
    }

    public function formatRecommendList(&$list)
    {
        if (empty($list)) {
            return [];
        }
        foreach ($list as $item) {
            $stock = Stock::where('product_id', '=', $item->product_id)->orderBy('price', 'ASC')->first();
            $product = Product::find($item->product_id);
            $item->product_name = $product->name;
            $item->sales_volume = $product->sales_volume;
            $item->cover = $stock->cover;
            $item->price = $stock->price;
            $item->origin_price = $stock->origin_price;
        }
        return $list;
    }

    public function countProduct($store = 0, $state = 0, $review = 0, $deleted = 0)
    {
        $db = DB::table('products');
        if ($store) {
            $db->where('store_id', '=', $store);
        }
        if ($state) {
            $db->where('state', '=', $state - 1);
        }
        if ($review) {
            $db->where('review', '=', $review - 1);
        }
        if ($deleted) {
            $db->where('deleted', '=', $deleted - 1);
        }
        return $db->count();
    }

}
