<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\LoginPost;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * 后台用户登录
     *
     * @param LoginPost $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginPost $post)
    {
//        dd($post);
        $username = $post->username;
        $password = $post->password;
        if (Auth::attempt(['username'=>$username,'password'=>$password],false)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'用户名或密码错误！'
        ],401);
    }

}
