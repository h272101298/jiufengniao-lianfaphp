<?php
namespace App\Modules\Role;
use App\Modules\Role\Model\Permission;
use App\Modules\Role\Model\Role;
use App\Modules\Role\Model\RolePermission;
use App\Modules\Role\Model\RoleUser;
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/6/15
 * Time: 下午4:41
 */
trait RoleHandle
{
    public function getUserRole($token)
    {
        $uid = getRedisData($token);
        $roleId = RoleUser::where('user_id','=',$uid)->first();
        $role = $roleId?Role::find($roleId):null;
        return $role;
//        $role =
    }
    public function getUserPermissions($token)
    {
        $uid = getRedisData($token);
        $roleId = RoleUser::where('user_id','=',$uid)->first();
        $permissionId = RolePermission::where('role_id','=',$roleId)->pluck('permission_id')->toArray();
        return Permission::whereIn('id',$permissionId)->get();
    }
    public function getRoles($page,$limit,$name)
    {
        $db = DB::table('roles');
        if ($name){
            $db->where('display_name','like','%'.$name.'%');
        }
        $roles = $db->limit($limit)->offset(($page-1)*$limit)->get();
        return $roles;
    }
    public function getRolePermissions($role_id)
    {
        $permissionId = RolePermission::where('role_id','=',$role_id)->pluck('permission_id')->toArray();
        $permissions = Permission::whereIn('id',$permissionId)->get();
        return $permissions;
    }
    public function getPermissions()
    {

    }
    public function getPermission($id)
    {
        $permission = Permission::find($id);
        return $permission;
    }
    public function delPermission($id)
    {
        $permission = Permission::find($id);
        if ($permission->delete()){
            return true;
        }
        return false;
    }
    public function createRole($data,$id=0)
    {
        if ($id){
            $role = Role::find($id);
        }else{
            $role = new Role();
        }
        foreach ($data as $key=>$value){
            $role->$key = $value;
        }
        if ($role->save()){
            return true;
        }
        return false;
    }
    public function delRole($id)
    {
        $role = Role::findOrFail($id);
        if ($role->delete()){
            return true;
        }
        return false;
    }

}