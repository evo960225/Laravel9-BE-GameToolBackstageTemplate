<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRoleHasPermission
{

    public function handle(Request $request, Closure $next, int $permission_id)
    {
        if ($request->user()->role->name === 'admin') {
            return $next($request);
        }
        $role = $request->user()->role;
        $permissions = $role->permissions->map(function($role_permission){
            return $role_permission->permission_id;
        });
        if ($permissions->contains($permission_id)) {
            return $next($request);
        }
        abort(403);
    }
}
