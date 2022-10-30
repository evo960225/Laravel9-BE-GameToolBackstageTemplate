<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user_list = User::with(['role:role_id,ch_name'])
                            ->select(['id','name','email','role_id','g2fa_enable'])
                            ->get();
        return response(['users' =>  $user_list], 201);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return response($user, 201);
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $chekc_user = Auth::user();
        if($chekc_user->id !== $user->id) {
            return response([], 401);
        }
        $filed = $request->validate([
            'name'=>'required|max:50',
        ]); 
        $user->name = $filed['name'];
        $user->save();
        $user->role;
        return response($user, 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response(['delete' => $user], 201);
    }

    public function current(Request $request){
        $user = Auth::user();
        $user->role;
        return response($user, 201);
    }
    

    public function showByEmail(Request $request)
    {
        $email = $request->email;
        $user = User::where('email', $email)->get();
        return response($user, 201);
    }
        
    public function changeUserRole(Request $request) {
        $fields = $request->validate([
            'email' => ['required', 'email'],
            'role_id' => ['required'],
        ]);
        $user = User::where('email', $fields['email'])->first();
        $user->update(['role_id' => $fields['role_id']]);
    }
    
}
