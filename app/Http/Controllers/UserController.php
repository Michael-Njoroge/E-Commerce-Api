<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::paginate(20);

        return $this->sendResponse($result=$users, $message="Users retrieved successfully");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|string|unique:users,email',
            'mobile' => 'required|string|unique:users,mobile',
            'password' => 'required|string|min:6'
        ]);

        $rawPassword = $data['password'];
        $data['password'] = Hash::make($rawPassword);
        $email = $data['email'];
        $mobile = $data['mobile'];

        $user = User::where('email',$email)->orWhere('mobile',$mobile)->first();

        if($user){
            return $this->sendError($error = 'User details already exists', $code = 403);
        }

        $savedData = User::create($data);
        
        return $this->sendResponse($result=$savedData, $message="User created successfully");
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $user = User::where('id',$user->id)->first();
        if($user){
            return $this->sendResponse($result=$user, $message="User retrieved successfully");
        }

        return $this->sendError($error="User not found");
       
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|string|unique:users,email',
            'mobile' => 'required|string|unique:users,mobile',
         ]);


        $user = User::where('email',$user->email)->orWhere('mobile',$user->mobile)->first();

        if(!$user){
            return $this->sendError($error="User not found");
        }

        $savedData = $user->update($data);
        
        return $this->sendResponse($result=$savedData, $message="User updated successfully");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user = User::where('id',$user->id)->first();

        if(!$user){
            return $this->sendError($error="User not found");
        }
        
        $user->delete();
        return $this->sendResponse($result='', $message="User deleted successfully");
    }
}