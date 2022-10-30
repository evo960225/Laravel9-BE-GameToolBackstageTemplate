<?php

namespace App\Functions;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Hash;

class CheckCommander 
{
    static public function checkPassword(Request $request)
    {

        $pwd = $request->input('password');  
        $user = $request->user();
        if (!$user || !Hash::check($pwd, $user->password)) {
            abort(401, '密碼錯誤！');
        }
    }

    
}