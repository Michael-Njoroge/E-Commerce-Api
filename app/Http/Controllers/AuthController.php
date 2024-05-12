<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
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

        $token = $user->createToken('access_token')->plainTextToken();

        return $this->sendResponse($message='Access token',$result=$token);
    }
}