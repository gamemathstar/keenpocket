<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;

class AuthController extends Controller
{
    //

    public function register(Request $request)
    {
        $fields = $request->validate([
            'name'=>"required|string",
            'email'=>"required|email",#|unique:users,email",
            'phone_number'=>'required|string',#|unique:users,phone_number',
            'password'=>'required|string'
        ]);
        $user = User::where("phone_number",$request->phone_number)->first();
        if($user){
            if($user->email==$user->phone_number){
                $user->email = $request->email;
                $user->password = $request->password;
                $user->save();
            }else{
                $request->validate([
                    'email'=>"required|email|unique:users,email",
                    'phone_number'=>'required|string|unique:users,phone_number'
                ]);
            }
        }else{
            $user = User::create([
                'name'=>$fields['name'],
                'email'=>$fields['email'],
                'username'=>$fields['phone_number'],
                'phone_number'=>$fields['phone_number'],
                'password'=>bcrypt($fields['password']),
            ]);
        }

        $token =   $user->createToken('keen_-_pocket')->plainTextToken;

        return response(compact('user','token'),200);

    }
    public function login(Request $request)
    {
        $fields = $request->validate([
            'phone_number'=>'required|string|exists:users,phone_number',
            'password'=>'required|string'
        ]);

        $user = User::where('phone_number',$fields['phone_number'])->first();
        if($user && Hash::check($fields['password'],$user->password)){
            $user->tokens()->delete();
            $token =   $user->createToken('keen_-_pocket')->plainTextToken;
            $status = 1;
            return response(compact('status','token'),200);
        }


        return response(['message'=>"Invalid Credentials"],200);

    }

    public function logout()
    {
        auth()->user()->tokens()->delete();
        return True;
    }
}
