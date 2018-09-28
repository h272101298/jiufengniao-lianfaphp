<?php
/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/6/30
 * Time: 下午4:15
 */

namespace App\Modules\Order;


use App\Libraries\ExpressSearch;
use App\Libraries\Wxxcx;
use App\Modules\GroupBuy\Model\GroupBuyJoin;
use App\Modules\GroupBuy\Model\GroupBuyList;
use App\Modules\Order\Model\AddressSnapshot;
use App\Modules\Order\Model\Order;
use App\Modules\Order\Model\OrderType;
use App\Modules\Order\Model\Refuse;
use App\Modules\Order\Model\StockSnapshot;
use App\Modules\Product\Model\Product;
use App\Modules\Product\Model\ProductDetailSnapshot;
use App\Modules\Product\Model\Stock;
use App\Modules\Score\Model\ExchangeRecord;
use App\Modules\Store\Model\Express;
use App\Modules\Store\Model\ExpressConfig;
use App\Modules\Store\Model\Store;
use App\Modules\System\Model\TxConfig;
use App\Modules\WeChatUser\Model\WeChatUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

trait OrderHandle
{
    public function addOrder($id,$data)
    {
        if ($id){
            $order = Order::find($id);
        }else{
            $order = new Order();
        }
        foreach ($data as $key=>$value){
            $order->$key = $value;
        }
        if ($order->save()){
            return $order->id;
        }
        return false;
    }
    public function getUserOrder($user_id)
    {
        $orders = Order::where('user_id','=',$user_id)->orderBy('id','DESC')->get();
        return $orders;
    }
    public function getOrderById($id)
    {
        return Order::findOrFail($id);
    }
    public function getOrderByNumber($number)
    {
        return Order::where('number','=',$number)->first();
    }
    public function addAddressSnapshot($order_id,$data)
    {
        $snapshot = new AddressSnapshot();
        $snapshot->order_id = $order_id;
        foreach ($data as $key=>$value){
            $snapshot->$key = $value;
        }
        if ($snapshot->save()){
            return true;
        }
        return false;
    }
    public function addStockSnapshot($order_id,$data,$id=0)
    {
        if ($id){
            $snapshot = StockSnapshot::findOrFail($id);
        }else{
            $snapshot = new StockSnapshot();
            $snapshot->order_id = $order_id;
        }
        foreach ($data as $key=>$value){
            $snapshot->$key = $value;
        }
        if ($snapshot->save()){
            return true;
        }
        return false;
    }
    public function closeOrder($order_id)
    {
        $count = StockSnapshot::where('order_id','=',$order_id)->where('is_assess','=',0)->count();
        if ($count==0){
            $order = Order::find($order_id);
            $order->state = 'closed';
            $order->save();
        }
        return true;
    }
    public function getMyOrders($user_id,$page=1,$limit=10,$state='',$order_id=null)
    {
        $db = Order::where('user_id','=',$user_id);
        if ($state){
            if ($state=='finished'){
                $db->whereIn('state',['closed','finished']);
            }else{
                $db->where('state','=',$state);
            }
        }
        if (!empty($order_id)){
            $db->whereIn('id',$order_id);
        }
        $count = $db->count();
        $data = $db->orderBy('id','DESC')->limit($limit)->offset(($page-1)*$limit)->get()->toArray();
        $data = $this->formatMyOrders($data);
        return [
            'data'=>$data,
            'count'=>$count
        ];
    }

    public function formatMyOrders(&$orders)
    {
        $data = [];
        if (empty($orders)){
            return $data;
        }
        for($i=0;$i<count($orders);$i++) {

            $snapshots = StockSnapshot::where('order_id','=',$orders[$i]['id'])->get()->toArray();
            $store = array_column($snapshots,'store_id');
            $store = array_unique($store);
            if ($orders[$i]['state']=='canceled'){
                $refuse = Refuse::where('order_id','=',$orders[$i]['id'])->pluck('state')->first();
                if (empty($refuse)){
                    $data[$i]['refuse'] = '';
                }else{
                    if ($refuse==2){
                        $data[$i]['refuse'] = '已退款';
                    }else{
                        $data[$i]['refuse'] = '待处理';
                    }
                }

            }
            $data[$i]['orderid'] = $orders[$i]['number'];
            $data[$i]['orderprice'] = $orders[$i]['price'];
            $data[$i]['state'] = $orders[$i]['state'];
            $data[$i]['type'] = OrderType::where('order_id','=',$orders[$i]['id'])->pluck('type')->first();
            $data[$i]['score'] = ExchangeRecord::where('order_id','=',$orders[$i]['id'])->first();
            for ($k=0;$k<count($store);$k++){
//                dd($store[$k]);
                $data[$i]['shop'][$k]['shopname'] = Store::find($store[$k])->name;
                $data[$i]['shop'][$k]['shopid'] = $store[$k];
                $store_id = $store[$k];
//                dd($snapshots)
                $swapCarts = array_filter($snapshots,function ($item) use($store_id){
                    return $item['store_id'] == $store_id;
                });
//                dd($snapshots);
                if (!empty($swapCarts)){
                    for ($j=0;$j<count($swapCarts);$j++){
//                        dd($swapCarts);
                        //$stock = Stock::find($swapCarts[$j]['stock_id']);
                        $swapCarts[$j]['goodid'] = $swapCarts[$j]['stock_id'];
                        $swapCarts[$j]['shopid'] = $store[$k];
                        $swapCarts[$j]['goodname'] = $swapCarts[$j]['product'];
                        $swapCarts[$j]['goodpic'] = $swapCarts[$j]['cover'];
                        $swapCarts[$j]['goodprice'] = $swapCarts[$j]['price'];
                        $swapCarts[$j]['goodnum'] = $swapCarts[$j]['number'];
                        $swapCarts[$j]['goodformat'] = $swapCarts[$j]['detail'];
                    }
                }
                $data[$i]['shop'][$k]['goods'] = $swapCarts;
            }
        }
        return $data;
    }

    public function getOrders($page,$limit,$start,$end,$number,$idArray=null,$user_id=null,$state='',$store_id=0)
    {
        $db = DB::table('orders');
        if ($start){
            $db->whereBetween('created_at',[$start,$end]);
        }
        if ($store_id){
            $db->where('store_id','=',$store_id);
        }
        if ($number!=''){
            $db->where('number','like','%'.$number.'%');
        }
        if ($user_id){
            $db->whereIn('user_id',$user_id);
        }
        if ($idArray){
            $db->whereIn('id',$idArray);
        }
        if ($state){
            if ($state=='finished'){
                $db->whereIn('state',['closed','finished']);
            }else{
                $db->where('state','=',$state);
            }
        }
        $count = $db->count();
        $data = $db->orderBy('id','DESC')->limit($limit)->offset(($page-1)*$limit)->get();
        return [
            'count'=>$count,
            'data'=>$data
        ];
    }
    public function getNewestOrders($store_id)
    {
        $db = DB::table('orders');
        if ($store_id){
            $db->where('store_id','=',$store_id);
        }
        $db->whereDate('created_at',date());
        $count = $db->count();
        $data = $db->get();
        return [
            'count'=>$count,
            'data'=>$data
        ];
    }
    public function formatOrders(&$orders)
    {
        if (empty($orders)){
            return [];
        }
        for ($i=0;$i<count($orders);$i++){
            $user = WeChatUser::find($orders[$i]->user_id);
            $store = Store::find($orders[$i]->store_id);
            $orders[$i]->user = $user?$user->nickname:'';
            $orders[$i]->store = $store?$store->name:'';
            $orders[$i]->groupState = 0;
            $type = $this->getOrderTypeByOrderId($orders[$i]->id);
            if (!empty($type)){
                if ($type->type=='groupCreate'){
                    $list = GroupBuyList::where('order_id','=',$orders[$i]->id)->first();
                    if (!empty($list)){
                        $orders[$i]->groupState = $list->state;
                    }
                }elseif($type->type=='groupJoin'){
                    $join = GroupBuyJoin::where('order_id','=',$orders[$i]->id)->first();
                    $list = GroupBuyList::find($join->list_id);
                    if (!empty($list)){
                        $orders[$i]->groupState = $list->state;
                    }
                }
                $orders[$i]->type = $type->type;
            }else{
                $orders[$i]->type = 'origin';
            }
        }
    }
    public function formatExcelOrders($orders)
    {
        $status = [
            'created'=>'已下单',
            'paid'=>'已支付',
            'delivery'=>'配送中',
            'finished'=>'已完成',
            'canceled'=>'已取消',
            'closed'=>'已完成'
        ];
        $data = [];
        if (empty($orders)){
            return [];
        }
        for ($i=0;$i<count($orders);$i++){
            $swap = [];
            $user = WeChatUser::find($orders[$i]->user_id);
            $address = AddressSnapshot::where('order_id','=',$orders[$i]->id)->first();
            $swap['id'] = $i+1;
            $swap['number'] = $orders[$i]->number.' ';
            $swap['user'] = $user?filterEmoji($user->nickname):'';
            $swap['amount'] = $orders[$i]->price. ' ';
            $swap['delivery'] = $orders[$i]->delivery==1?'自提':'配送';
            $swap['state'] = $status[$orders[$i]->state];
            $swap['time'] = $orders[$i]->created_at;
            $swap['name'] = !empty($address)?$address->name:"";
            $swap['phone'] = !empty($address)?$address->phone.' ':"";
            $swap['address'] = !empty($address)?$address->address:"";
            array_push($data,$swap);
        }
        return $data;
    }
    public function getOrderIdByExpressName($name)
    {
        $idArray = AddressSnapshot::where('name','like','%'.$name.'%')->pluck('order_id')->toArray();
        return $idArray;
    }
    public function getOrderIdByStoreName($name)
    {
        $storeId = Store::where('name','like','%'.$name.'%')->pluck('id')->toArray();
        if (!empty($storeId)){
            $orderId = StockSnapshot::whereIn('store_id',$storeId)->pluck('order_id')->toArray();
        }else{
            $orderId = [];
        }
        return $orderId;

    }
    public function getOrdersIdByOrderType($type)
    {
        $db = DB::table('order_types');
        if ($type=='groupCreate'||$type=='groupJoin'){
            $db->whereIn('type',['groupCreate','groupJoin']);
        }else{
            $db->where('type','=',$type);
        }
        return $db->pluck('order_id')->toArray();
    }
    public function formatOrder(&$order)
    {
        $order->user = WeChatUser::find($order->user_id);
        $order->store = Store::find($order->store_id);
        $order->address = AddressSnapshot::where('order_id','=',$order->id)->get();
        $order->stocks = StockSnapshot::where('order_id','=',$order->id)->get();
    }
    public function getExpressInfo($order_id)
    {
        $order = Order::where('number','=',$order_id)->firstOrFail();
        $config = ExpressConfig::first();
        if (empty($config)){
            return false;
        }
        $search = new ExpressSearch($config->business_id,$config->api_key);
        $express = Express::find($order->express);
        $data = $search->getOrderTracesByJson($express->code,$order->express_number);
        $data = json_decode($data);
        if (!isset($data->Traces)){
            return false;
        }
        $data = $data->Traces;
        $data = array_reverse($data);
        return $data;
    }
    public function getStockSnapshot($id)
    {
        return StockSnapshot::findOrFail($id);
    }
    public function addRefuse($id,$order_id,$data)
    {
        if ($id){
            $refuse = Refuse::find($id);
        }else{
            $refuse = new Refuse();
            $refuse->order_id = $order_id;
        }
        foreach ($data as $key=>$value){
            $refuse->$key = $value;
        }
        if ($refuse->save()){
            return true;
        }
        return false;
    }
    public function getRefuses($store_id,$page,$limit)
    {
        $count = Refuse::where('store_id','=',$store_id)->count();
        $refuses = Refuse::where('store_id','=',$store_id)->limit($limit)->offset(($page-1)*$limit)->orderBy('id','DESC')->get();
        $this->formatRefuses($refuses);
        return [
            'data'=>$refuses,
            'count'=>$count
        ];
    }
    public function formatRefuses(&$refuses)
    {
        if (empty($refuses)){
            return [];
        }
        foreach ($refuses as $refuse) {
            $order = Order::find($refuse->order_id);
            $order->store = Store::find($order->store_id);
            $order->user = WeChatUser::find($order->user_id);
            $refuse->order = $order;
        }
        return $refuses;
    }
    public function refuse($id)
    {
        $config = TxConfig::first();
        $refuse = Refuse::find($id);
        $wxpay = getWxPay();
        $path = base_path().'/public/';
        $order = Order::find($refuse->order_id);
        $total_fee = Order::where('group_number','=',$order->group_number)->sum('price');
        $data = $wxpay->refund($order->transaction_id,$order->number,$total_fee*100,$order->price*100,$config->mch_id,$path.$config->ssl_cert,
            $path.$config->ssl_key);
        if ($data['return_code']=='FAIL'){
            $refuse->state = 3;
            $refuse->save();
            //var_dump($data);
            return false;
        }else{
            if ($data['result_code']=='FAIL'){
                $refuse->state = 3;
                $refuse->save();
                //var_dump($data);
                return false;
            }else{
                $refuse->state = 2;
                ///var_dump($data);
                $refuse->save();
                return true;
            }
        }
        return false;
    }
    public function countOrders($store_id=0,$state='',$created='',$user_id=0)
    {
        $db = DB::table('orders');
        if ($store_id){
            $db->where('store_id','=',$store_id);
        }
        if ($state){
            $db->where('state','=',$store_id);
        }
        if ($user_id){
            $db->where('user_id','=',$user_id);
        }
        if ($created){
            $db->whereDate('created_at',$created);
        }

        return $db->count();

    }
    public function countUserOrders($user_id,$state)
    {
        return Order::where('user_id','=',$user_id)->where('state','=',$state)->count();
    }
    public function countSales($store_id=0,$created='')
    {
        $OrderDB = Order::where('state','!=','created')->where('state','!=','canceled');
        if ($store_id){
            $OrderDB->where('store_id','=',$store_id);
        }
        if ($created){
            $OrderDB->whereDate('created_at',$created);
        }
        $idArray = $OrderDB->pluck('id')->toArray();
        return StockSnapshot::whereIn('order_id',$idArray)->sum('number');
    }
    public function addOrderType($id=0,$data)
    {
        if ($id){
            $type = OrderType::find($id);
        }else{
            $type = new OrderType();
        }
        foreach ($data as $key => $value){
            $type -> $key = $value;
        }
        if ($type->save()){
            return true;
        }
        return false;
    }
    public function getOrderTypeByOrderId($orderId)
    {
        return OrderType::where('order_id','=',$orderId)->first();
    }
    public function getOrderTypeByBargain($bargain_id)
    {
        return OrderType::where('type','=','bargain')->where('promotion_id','=',$bargain_id)->first();
    }
    public function getOrderTypesIdByBargain($bargain_id)
    {
        return OrderType::where('type','=','bargain')->where('promotion_id','=',$bargain_id)->pluck('order_id')->toArray();
    }
    public function countOrder($idArray,$user_id)
    {
        return Order::whereIn('id',$idArray)->where('user_id','=',$user_id)->count();
    }
    public function getOrdersIdByType($type)
    {
        return OrderType::where('type','=',$type)->pluck('order_id')->toArray();
    }
}