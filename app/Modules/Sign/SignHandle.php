<?php
/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/8/7
 * Time: 上午11:38
 */

namespace App\Modules\Sign;


use App\Modules\Sign\Model\SignConfig;
use App\Modules\Sign\Model\SignRecord;

trait SignHandle
{
    public function addSignRecord($user_id)
    {
        $record = new SignRecord();
        $record->user_id = $user_id;
        if ($record->save()){
            return true;
        }
        return false;
    }
    public function checkSign($user_id,$today=1)
    {
        $db = SignRecord::where('user_id','=',$user_id);
        if ($today){
            $db->whereDate('created_at', date('Y-m-d',time()));
        }
        return $db->count();
    }
    public function setContinueSign($user_id,$data)
    {
        $data = serialize($data);
        setRedisData('sign'.$user_id,$data);
    }
    public function getContinueSign($user_id)
    {
        $data = getRedisData('sign'.$user_id);
        if (!empty($data)){
            return unserialize($data);
        }
        return false;
    }
    public function addSignConfig($id,$data)
    {
        if ($id){
            $config = SignConfig::find($id);
        }else{
            $config = new SignConfig();
        }
        foreach ($data as $key => $value){
            $config->$key = $value;
        }
        if ($config->save()){
            return true;
        }
        return false;
    }
    public function getSignConfig($day)
    {
        return SignConfig::where('days','=',$day)->first();
    }
    public function getSignConfigs()
    {
        return SignConfig::all()->toArray();
    }
}