<?php
namespace App\Modules\Address;
use App\Modules\Address\Model\Address;
use App\Modules\Address\Model\UserAddress;
use function GuzzleHttp\Psr7\uri_for;

/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/6/8
 * Time: 上午10:28
 */
trait AddressHandle
{
//    private $user_id;

//$this->user_id = $user_id;
//}public function __construct($user_id)
//    {

    function addAddress($token,$data,$id=0)
    {
        $uid = getRedisData($token);
        if ($id){
            $address = Address::find($id);
            UserAddress::where('user_id','=',$uid)->where('address_id','=',$id)->delete();
        }else{
            $address = new Address();
        }

        foreach ($data as $key=>$value){
            $address->$key = $value;
        }
        if ($address->save()){
            $userAddress = new UserAddress();
            $userAddress->user_id = $uid;
            $userAddress->address_id = $address->id;
            if ($userAddress->save()){
                return true;
            }
        }
        return false;
    }
    public function listAddress()
    {

    }
    public function listMyAddress($user_id,$limit=10,$page=1)
    {
        $addressIdArr = UserAddress::where('user_id','=',$user_id)->orderBy('is_default','DESC')->pluck('address_id')->toArray();
        $addresses = Address::whereIn('id',$addressIdArr)->limit($limit)->offset(($page-1)*$limit)->get();
        return $addresses;
    }
    public function getAddress($id)
    {
        $address = Address::findOrFail($id);
        return $address;
    }
    public function delAddress($token,$id)
    {
        $uid = getRedisData($token);
        $userAddress = UserAddress::where('user_id','=',$uid)->where('address_id','=',$id)->first();
        if (empty($userAddress)){
            throw new \Exception('无权操作！',403);
        }
        $address = Address::findOrFail($id);
        if ($address->delete()){
            UserAddress::where('user_id','=',$uid)->where('address_id','=',$id)->delete();
            return true;
        }
        return false;
    }
    public function isDefaultAddress($id)
    {
        return UserAddress::where('address_id','=',$id)->pluck('is_default')->first();
    }
    public function getDefaultAddress($user_id)
    {
        $id = UserAddress::where('user_id','=',$user_id)->where('is_default','=',1)->pluck('address_id')->first();
        if ($id){
            return Address::find($id);
        }
        return [];
    }
    public function setDefaultAddress($token,$id)
    {
        $uid = getRedisData($token);
        $userAddress = UserAddress::where('user_id','=',$uid)->where('address_id','=',$id)->first();
        if (empty($userAddress)){
            throw new \Exception('无权操作！',403);
        }
        UserAddress::where('user_id','=',$uid)->update(['is_default'=>0]);
        $userAddress->is_default = 1;
        $userAddress->save();
        return true;
//        $address = Address::findOrFail($id);
//        if ($address->delete()){
//            UserAddress::where('user_id','=',$uid)->where('address_id','=',$id)->delete();
//            return true;
//        }
        return false;
    }
}