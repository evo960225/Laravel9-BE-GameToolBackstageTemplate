<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $role_list = Role::select('id', 'role_id', 'ch_name')->get();
        return response(['roles' =>  $role_list], 201);
    }

    public function show(Role $role)
    {
        // return with permissions
        $role->permissions;
        return response($role, 201);
    }
    
    public function update(Request $request, Role $role)
    {
        $permissions_id = $request['permissions_id'];
        if (!$permissions_id) { 
            return response(
                ['msg' => 'param is null'],
                401);
        }
        // 全部刪除
        $role->permissions()->delete();
        
        // 為接收的編號建立資料
        $updateArray = [];
        foreach($permissions_id as $id) {
            array_push($updateArray, ['permission_id' => $id]);
        }
        $role->permissions()->createMany($updateArray);
        $role->permissions;
        return response($role, 201);
    }

}
