<?php
/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/6/21
 * Time: 下午2:47
 */

namespace App\Modules\Store;


use App\Modules\Product\Model\Product;
use App\Modules\Product\Model\Stock;
use App\Modules\Store\Model\Express;
use App\Modules\Store\Model\ExpressConfig;
use App\Modules\Store\Model\Store;
use App\Modules\Store\Model\StoreCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait StoreHandle
{
    public function addStoreCategory($id = 0, $title)
    {
        if ($id) {
            $category = StoreCategory::find($id);
        } else {
            $category = new StoreCategory();
        }
        $category->title = $title;
        if ($category->save()) {
            return true;
        }
        return false;
    }

    public function getStoreCategories($page, $limit)
    {
        return [
            'count' => StoreCategory::count(),
            'data' => StoreCategory::limit($limit)->offset(($page - 1) * $limit)->get()
        ];
    }

    public function delStoreCategory($id)
    {
        $category = StoreCategory::findOrFail($id);
        if ($category->delete()) {
            return true;
        }
        return false;
    }

    public function addExpress($id = 0, $store_id, $title, $code)
    {
        if ($id) {
            $express = Express::find($id);
        } else {
            $express = new Express();
        }
        $express->store_id = $store_id;
        $express->title = $title;
        $express->code = $code;
        if ($express->save()) {
            return true;
        }
        return false;
    }

    public function getExpresses($store_id = 0, $page, $limit, $title = '', $code)
    {
        $db = DB::table('expresses');
        if ($store_id) {
            $db->where('store_id', '=', $store_id);
        }
        if ($title) {
            $db->where('title', 'like', '%' . $title . '%');
        }
        if ($code) {
            $db->where('code', 'like', '%' . $code . '%');
        }
        $count = $db->count();
        $data = $db->get();
        return [
            'count' => $count,
            'data' => $data
        ];
    }

    public function delExpress($id)
    {
        $express = Express::findOrFail($id);
        if ($express->store_id != getStoreId()) {
            return false;
        }
        if ($express->delete()) {
            return true;
        }
        return false;
    }

    public function addStore($id = 0, $user_id, $data)
    {
        if ($id) {
            $store = Store::find($id);
        } else {
            $store = new Store();
            $store->user_id = $user_id;
        }
        foreach ($data as $key => $value) {
            $store->$key = $value;
        }
        if ($store->save()) {
            return true;
        }
        return false;
    }

    public function getUserStoreCount($user_id, $id)
    {
        return Store::where('user_id', '=', $user_id)->where('id', '!=', $id)->count();
    }

    public function getUserStore($user_id)
    {
        return Store::where('user_id', '=', $user_id)->first();
    }
    public function getStoreById($id)
    {
        return Store::find($id);
    }
    public function getStores($name = '', $page, $limit)
    {
        $db = DB::table('stores');
        if ($name) {
            $db->where('name', 'like', $name);
        }
        $count = $db->count();
        $data = $db->orderBy('id', 'DESC')->limit($limit)->offset(($page - 1) * $limit)->get();
        return [
            'data' => $data,
            'count' => $count
        ];
    }

    public function getStoresId($name)
    {
        $db = DB::table('stores');
        if ($name) {
            $db->where('name', 'like', '%' . $name . '%');
        }
        return $db->pluck('id')->toArray();
    }

    public function getStoresIdByStockId($idArray)
    {
        $productId = Stock::whereIn('id', $idArray)->pluck('product_id')->toArray();
        $storesId = Product::whereIn('id', $productId)->pluck('store_id')->toArray();
        return array_unique($storesId);
    }

    public function addStoreExpressConfig($store_id, $businessId, $apiKey)
    {
        $config = ExpressConfig::where('store_id', '=', $store_id)->first();
        if (empty($config)) {
            $config = new ExpressConfig();
            $config->store_id = $store_id;
        }
        $config->business_id = $businessId;
        $config->api_key = $apiKey;
        if ($config->save()) {
            return true;
        }
        return false;
    }

    public function getStoreExpressConfig($store_id)
    {
        $config = ExpressConfig::where('store_id', '=', $store_id)->first();
        if (empty($config)) {
            $config = new ExpressConfig();
        }
        return $config;
    }
}