<?php

namespace App\Http\Middleware;

use App\Modules\Role\Model\Permission;
use App\Modules\Role\Model\RolePermission;
use App\Modules\Role\Model\RoleUser;
use Closure;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next,$permission)
    {
        $role_id = RoleUser::where('user_id','=',Auth::id())->pluck('role_id')->first();
        $name = explode('|',$permission);
        $permissionId = Permission::whereIn('name',$name)->pluck('id')->toArray();
        if (empty($permissionId)){
            return jsonResponse([
                'msg'=>'无权访问！'
            ],403);
        }
        $idArray = RolePermission::where('role_id','=',$role_id)->pluck('permission_id')->toArray();
        $intersection = array_intersect($permissionId, $idArray);
        if (!empty($intersection)){
            return $next($request);
        }
        return jsonResponse([
            'msg'=>'无权访问！'
        ],403);
    }
}
