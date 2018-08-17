<?php
/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/8/17
 * Time: 下午2:29
 */

namespace App\Modules\Score;


use App\Modules\Score\Model\UserScore;

trait ScoreHandle
{
    public function getUserScore($user_id)
    {
        return UserScore::where('user_id','=',$user_id)->pluck('score')->first();
    }
    public function addUserScore($user_id,$score)
    {
        $userScore = UserScore::where('user_id','=',$user_id)->first();
        if (empty($userScore)){
            $userScore = new UserScore();
            $userScore->score = 0 ;
        }
        $userScore->score += $score;
        if ($userScore->save()){
            return true;
        }
        return false;
    }
}