<?php

namespace App\Http\Controllers\V3;

use App\Modules\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class PrizeController extends Controller
{
    //
    public function __construct()
    {
        $this->handle = new User();
    }

    public function addPrizeConfig(Request $post)
    {
        $data = [
            'share_score'=>$post->share_score?$post->share_score:0,
            'prize_score'=>$post->prize_score?$post->prize_score:0,
            'register_score'=>$post->register_score?$post->register_score:0
        ];
        if ($this->handle->addPrizeConfig($data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function getPrizeConfig()
    {
        $data = $this->handle->getPrizeConfig();
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function addPrize(Request $post)
    {
        $id = $post->id?$post->id:0;
        $data = [
            'name'=>$post->name?$post->name:'',
            'score'=>$post->score?$post->score:0,
            'count'=>$post->count?$post->count:0,
            'num'=>$post->num?$post->num:0,
            'icon'=>$post->icon?$post->icon:''
        ];
        if ($this->handle->addPrize($id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function getPrizes()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $data = $this->handle->getPrizes($page,$limit);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function delPrize()
    {
        $id = Input::get('id');
        if ($this->handle->delPrize($id)){
            return  jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function prize(Request $post)
    {
        $uid = getRedisData($post->token);
        $config = $this->handle->getPrizeConfig();
        $score = $this->handle->getUserScore($uid);
        if ($score<$config->prize_score){
            return response()->json([
                'msg'=>'积分余额不足！'
            ]);
        }
        $this->handle->addUserScore2($uid,0-$config->prize_score);
        $data = [
            'user_id'=>$uid,
            'type'=>1,
            'score'=>$config->prize_score,
            'remark'=>'抽奖消耗'
        ];
        $this->handle->addScoreUseRecord(0,$data);
        $prizes = $this->handle->getPrizesArray();
        for ($i=0;$i<count($prizes);$i++){
            $arr[$i] = $prizes[$i]['count'];
        }
        $rid = $this->get_rand($arr);
        $res['yes'] = $prizes[$rid];
        unset($prizes[$rid]);
        shuffle($prizes);
        $prize_count = count($prizes);
        for($i=0;$i<$prize_count;$i++){
            $pr[] = $prizes[$i]['name'];
        }
        if (empty($pr)){
            return response()->json([
                'code'=>'200',
                'msg'=>'SUCCESS',
                'data'=>[
                    'result'=>'谢谢',
                    'message'=>'并没有中奖。。。'
                ]
            ]);
        }
        $res['no'] = $pr;
        //抽奖结果
        $ro = $res['yes'];
        if ($ro['score']==0){
            $message = '并没有中奖。。。';
        }else{
            $this->handle->addUserScore2($uid,$ro['score']);
            $data = [
                'user_id'=>$uid,
                'type'=>4,
                'score'=>$ro['score'],
                'remark'=>'抽奖奖励'
            ];
            $this->handle->addScoreRecord(0,$data);
            $prize = $this->handle->getPrize($ro['id']);
            $this->handle->addPrize($ro['id'],['num'=>$prize->num-1]);
            $message = '获得'.$ro['name'];
        }
        return response()->json([
            'code'=>'200',
            'msg'=>'SUCCESS',
            'data'=>[
                'result'=>$ro['name'],
                'message'=>$message
            ]
        ]);

    }
    function get_rand($proArr) {
        $result = '';
        $proSum = array_sum($proArr);
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset ($proArr);
        return $result;
    }
}
