<?php
namespace App\Modules\Role;
use App\Modules\Role\Model\DefaultRole;
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
    public function getUserRole($uid)
    {
        $roleId = RoleUser::where('user_id','=',$uid)->pluck('role_id')->first();
        $role = $roleId?Role::find($roleId):null;
        if (!empty($role)){
            $idArray = RolePermission::where('role_id','=',$role->id)->pluck('permission_id')->toArray();
            $permissions = Permission::whereIn('id',$idArray)->pluck('name')->toArray();
            $role->permissions = $permissions;
        }
        return $role;
//        $role =
    }
    public function getUserPermissions($uid)
    {
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
        $count = $db->count();
        $roles = $db->limit($limit)->offset(($page-1)*$limit)->get();
        if (!empty($roles)){
            foreach ($roles as $role){
                $idArray = RolePermission::where('role_id','=',$role->id)->pluck('permission_id')->toArray();
                $role->permissions = Permission::whereIn('id',$idArray)->get();
                $role->default = $this->checkDefaultRole($role->id);
            }
        }
        return [
            'count'=>$count,
            'data'=>$roles
        ];
    }
    public function getRolePermissions($role_id)
    {
        $permissionId = RolePermission::where('role_id','=',$role_id)->pluck('permission_id')->toArray();
        $permissions = Permission::whereIn('id',$permissionId)->get();
        return $permissions;
    }
    public function getPermissions()
    {
        $permissions = Permission::all();
        return $permissions;
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
    public function createRole($id=0,$data,$permission)
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
            if (!empty($permission)){
                RolePermission::where('role_id','=',$role->id)->delete();
                foreach ($permission as $item){
                    $swap = Permission::where('name','=',$item)->first();
                    if (empty($swap)){
                        $swap = new Permission();
                        $swap->name = $item;
                        $swap->display_name = $item;
                        $swap->save();
                    }
                    $rolePermission = new RolePermission();
                    $rolePermission->role_id = $role->id;
                    $rolePermission->permission_id = $swap->id;
                    $rolePermission->save();

                }
            }
            return true;
        }
        return false;
    }
    public function delRole($id)
    {
        $role = Role::findOrFail($id);
        if ($role->delete()){
            RolePermission::where('role_id','=',$id)->delete();
            return true;
        }
        return false;
    }
    public function createPermission($id=0,$data)
    {
        if ($id){
            $permission = Permission::find($id);
        }else{
            $permission = new Permission();
        }
        foreach ($data as $key=>$value){
            $permission->$key = $value;
        }
        if ($permission->save()){
            return true;
        }
        return false;
    }
    public function addDefaultRole($role_id)
    {
        $role = DefaultRole::first();
        if (empty($role)){
            $role = new DefaultRole();
        }
        $role->role_id = $role_id;
        if ($role->save()){
            return true;
        }
        return false;
    }
    public function checkDefaultRole($role_id,$filter=0)
    {
        $db = DB::table('default_roles');
        if ($role_id){
            $db->where('role_id','=',$role_id);
        }
        if ($filter){
            $db->where('role_id','!=',$role_id);
        }
        return $db->count();
    }
    public function delDefaultRole($role_id)
    {

    }
    public function getDefaultRole()
    {
        return DefaultRole::first();
    }
    public function addRoleUser($role_id,$user_id)
    {
        $role = RoleUser::where('user_id','=',$user_id)->first();
        if (empty($role)){
            $role = new RoleUser();
            $role->user_id = $user_id;
        }
        $role->role_id = $role_id;
        if ($role->save()){
            return true;
        }
        return false;
    }
}