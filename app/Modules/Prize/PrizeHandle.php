<?php
/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/9/27
 * Time: 上午11:07
 */

namespace App\Modules\Prize;


use App\Modules\Prize\Model\Prize;
use App\Modules\Prize\Model\PrizeConfig;
use Illuminate\Support\Facades\DB;

trait PrizeHandle
{
    public function addPrizeConfig($data)
    {
        $config = PrizeConfig::first();
        if (empty($config)){
            $config = new PrizeConfig();
        }
        foreach ($data as $key=>$value){
            $config->$key = $value;
        }
        if ($config->save()){
            return true;
        }
        return false;
    }
    public function getPrizeConfig()
    {
        return PrizeConfig::first();
    }
    public function addPrize($id,$data)
    {
        if ($id){
            $prize = Prize::find($id);
        }else{
            $prize = new Prize();
        }
        foreach ($data as $key => $value){
            $prize->$key = $value;
        }
        if ($prize->save()){
            return true;
        }
        return false;
    }
    public function getPrize($id)
    {
        return Prize::find($id);
    }
    public function delPrize($id)
    {
        $prize = Prize::find($id);
        return $prize->delete();
    }
    public function getPrizes($page=1,$limit=10)
    {
        $db = DB::table('prizes');
        $count = $db->count();
        $data = $db->orderBy('id','DESC')->limit($limit)->offset(($page-1)*$limit)->get();
        return [
            'data'=>$data,
            'count'=>$count
        ];
    }
    public function getPrizesArray()
    {
        return Prize::where('num','!=','0')->orWhere('score','=',0)->get()->toArray();
    }


}