<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Google2FA;

class AuthController extends Controller
{
    public function register(Request $request) {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|confirmed|min:6',
            'role_id' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return response($validator->getMessageBag(), 403);
        }

        $user = User::create([
            'name' => $request['name'],
            'email' => $request['email'],
            'password' => bcrypt($request['password']),
            'role_id' => $request['role_id'],
        ]);
        
        $role = $user->role->name;

        $response = [
            'user' => $user,
        ];

        return response($response, 201);
    }

    public function login(Request $request) {

        $fields = $request->validate([
            'email' => ['required', 'email'],
            'password' => 'required',
            /*'recaptcha' => 'required'*/
        ]);
        
        // verify reCaptcha
        /* 停用
        $secret_key = env('RECAPTCHA_SECRET_KEY');
        $recaptcha = $fields['recaptcha'];
        $url = env('RECAPTCHA_SITE', 'https://www.google.com/recaptcha/api/siteverify');
        $verify_res = Http::asForm()->post($url, [
            'secret' => $secret_key,
            'response' => $recaptcha
        ]);
        
        if (!$verify_res['success']) {
            return response([
                'message' => 'reCaptcha failed!'
            ], 401);
        }
        */

        // Check email
        $user = User::where('email', $fields['email'])->first();

        // Check password
        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return response([
                'message' => 'Bad creds'
            ], 401);
        }
        // Check 2FA
        if ($user->g2fa_enable){
            try {
                $fields = $request->validate([
                    'google2fa' => 'required',
                ]);
            } catch(Exception $exception) {
                return response(['2fa_reqire' => true], 201);
            }
            $user_key = $user->g2fa_key;
            if(!$this->check2fa($user_key, $request)){
                return response(['msg' => '2FA Key not correct!'], 401);
            }
        }
        
        
        $role = $user->role->name;
        $token = $user->createToken('apitoken',[$role])->plainTextToken;
 
        $response = response(['token' => $token]);
        return $response;
    }
    
    public function logout(Request $request) {

        $user = $request->user();
        if(!$user){
            return response(['error' => 'not found user!'],500);
        }
        $user->tokens()->delete();
        return response(['msg' => '帳號已登出！'], 201);
    }

    public function resetPassword(Request $request) {
        $fields = $request->validate([
            'old_password' => 'required|string',
            'password' => 'required|string|confirmed|min:6',
        ]);
        $user = $request->user();
        if (!$user || !Hash::check($fields['old_password'], $user->password)) {
            return response([
                'message' => 'Bad creds'
            ], 401);
        }
        $user->password = bcrypt($fields['password']);
        $user->save();
        return response( ['user' => $user ], 201);
    }

    // 用來確認登入狀態以及權限
    public function checkState(Request $request) {
        if(!$user = $request->user()){             
            if(!$user = Auth::guard('sanctum')->user()){
                return response([ 'state' => null ], 201);
            }
        }   
        $token = $user->currentAccessToken();
        $username = $user->name;
        $roles = $token->abilities;
        $permissions = $user->role->permissions;
        return response([
            'roles' =>  $roles,
            'username' =>  $username,
            'permissions' => $permissions
        ], 201);
    }


    // 2FA
    // --------------------------------------------------
    public function enable2fa(Request $request) {
        $user = Auth::user();
        $g2fa_key = Google2FA::generateSecretKey();
        $user->g2fa_key = encrypt($g2fa_key);
        $user->g2fa_enable = true;
        $user->save();
        $qrCode = Google2FA::getQRCodeInline(
            'Hshs',
            $user->email,
            $g2fa_key
        );
        return response([ 'qrcode' => $qrCode ], 201);
    } 

    public function get2faQrCode(Request $request) {
        $user = Auth::user();
        if(!$user->g2fa_enable) return response([ 'qrcode' => '' ], 201);
        $g2fa_key = decrypt($user->g2fa_key);
        $qrCode = Google2FA::getQRCodeInline(
            'Hshs',
            $user->email,
            $g2fa_key
        );
        return response([ 'qrcode' => $qrCode ], 201);
    } 

    public function disable2fa(Request $request) {
        $user = Auth::user();
        $user->g2fa_enable = false;
        $user->save();
        return response([ 'msg' => 'OK!' ], 201);
    } 

    public function check2fa(string $key, Request $request) {
        $g2fa_key = decrypt($key);
        $valid = Google2FA::verifyKey($g2fa_key, $request['google2fa']);
        return $valid;
    }
    
}
