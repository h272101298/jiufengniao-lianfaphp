<?php

namespace App\Http\Middleware;

use Closure;

class CheckToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$request->has('token')||!getRedisData($request->get('token'))){
            return jsonResponse([
                'msg'=>'登录过期，请重新登录！'
            ],401);
        }
//        $user = WechatUser::find(getUserToken($request->get('token')));
//        if ($user->state ==0){
//            return response()->json([
//                'code'=>'403',
//                'msg'=>'账号被禁用！'
//            ]);
//        }
        return $next($request);
    }
}
