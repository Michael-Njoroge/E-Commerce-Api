<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;

class AuthController extends Controller
{

    //Login User
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|string|exists:users,email',
            'password' => 'required|string'
        ]);
        
        $user = User::where('email',$data['email'])->first();

        $password = Hash::check($data['password'], $user->getAuthpassword());

        if(!$user && !$password){
            return $this->sendError($error="Invalid Credentials");
        }

        // $token = $user->createToken('access_token')->plainTextToken();
        $token = $user->createToken("access_token")->plainTextToken;

        $user->refreshToken = $token;
        $user->save();

        // $cookie = Cookie::make('access_token', $token, httpOnly:true, maxAge:72 * 60 * 60 * 1000);
        $cookie = Cookie::make('access_token', $user->id, minutes: 72 * 60 * 60 * 1000, httpOnly: true);

        return response()->json([
            'id' => $user->id,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'token' => $token,
        ])->withCookie($cookie);
    }

    //Login Admin
    public function loginAdmin(Request $request)
    {

        $data = $request->validate([
            'email' => 'required|email|string|exists:users,email',
            'password' => 'required|string'
        ]);
        
        $admin = User::where('email',$data['email'])->first();

        $password = Hash::check($data['password'], $admin->getAuthpassword());

        if(!$admin && !$password){
            return $this->sendError($error="Invalid Credentials");
        }

        if($admin->role !== 'admin'){
            return $this->sendError($error="Unauthorized");
        }

        $token = $admin->createToken("access_token")->plainTextToken;
        $admin->refreshToken = $token;
        $admin->save();

        $cookie = Cookie::make('access_token', $admin->id, minutes: 72 * 60 * 60 * 1000, httpOnly: true);


        return response()->json([
            'id' => $admin->id,
            'firstname' => $admin->firstname,
            'lastname' => $admin->lastname,
            'email' => $admin->email,
            'mobile' => $admin->mobile,
            'token' => $token,
        ])->withCookie($cookie);
    }
}