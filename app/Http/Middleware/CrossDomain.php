<?php

namespace App\Http\Middleware;

use Closure;

class CrossDomain
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
        $allowOrigin = config('allowOrigin');
        $response = $next($request);
        if (in_array($request->header('Origin'),$allowOrigin)){
            $response->header('Access-Control-Allow-Origin', $request->header('Origin'));
            $response->header('Access-Control-Allow-Headers', 'Origin, Access-Control-Request-Headers, SERVER_NAME, Access-Control-Allow-Headers, cache-control, token, X-Requested-With, Content-Type, Accept, Connection, User-Agent, Cookie, X-XSRF-TOKEN');
            $response->header('Access-Control-Allow-Methods', 'OPTIONS,GET, POST, PATCH, PUT, DELETE');
            $response->header('Access-Control-Allow-Credentials', 'true');
        }
        return $response;
    }
}
