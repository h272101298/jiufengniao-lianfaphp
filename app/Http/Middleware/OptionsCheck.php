<?php

namespace App\Http\Middleware;

use Closure;

class OptionsCheck
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
        echo $request->getMethod();
        if ($request->method()=='OPTIONS'){
            return response()->json([
                'ss'
            ]);
        }
        return $next($request);
    }
}
