<?php

namespace App\Http\Middleware;

use Closure;

class CheckStore
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
        if (getStoreId()){
            return $next($request);
        }
        return jsonResponse([
            'msg'=>'请先添加店铺！'
        ],400);
    }
}
