<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CurrentUserController extends Controller
{


    public function show(User $user)
    {
        $user = Auth::user();
        $user->role;
        return response($user, 201);
    }


    public function update(Request $request)
    {
        // 是否登入
        $auth_user = Auth::user();
        if(!$auth_user) {
            return response([], 401);
        }
        
        // 欄位驗證
        $filed = $request->validate([
            'name'=>'required|max:50',
            'email' => 'required|string'
        ]);
        
        // 取得資料
        $user = User::where('email', $auth_user->email)->first();
        if(!$user) return response([], 500);
        
        // 更新
        $user->update(['name' => $filed['name']]);
        $user->role;
        return response($user, 201);
    }
    

    
}
