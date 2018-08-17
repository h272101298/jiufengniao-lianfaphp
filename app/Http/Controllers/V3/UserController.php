<?php

namespace App\Http\Controllers\V3;

use App\Modules\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class UserController extends Controller
{
    //
    private $handle;
    public function __construct()
    {
        $this->handle = new User();
    }

    public function getUserScore()
    {
        $user_id = getRedisData(Input::get('token'));
        $score = $this->handle->getUserScore($user_id);
        if (empty($score)){
            $score = 0;
        }
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$score
        ]);
    }
}
