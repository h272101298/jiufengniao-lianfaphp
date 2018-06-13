<?php
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

    function addAddress()
    {
        $address = new \App\Modules\Address\Model\Address();
        return $address;
    }
    public function listAddress()
    {

    }
    public function listMyAddress($user_id,$limit=10,$page=1)
    {
        return \App\Modules\Address\Model\Address::where('user_id','=',$user_id)->limit($limit)->offset(($page-1)*$limit)->get();
    }
}