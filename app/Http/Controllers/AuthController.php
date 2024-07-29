<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed'
        ]);

        $user = User::create($fields);

        // will create a token and its hashed value will be stored in tokens table

        $token = $user->createToken($request->name);

        return [
            'user' => $user,
            'token' => $token->plainTextToken       // returns acutal token out of the array
        ];
    }


    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first(); // first reutrns an object and not an array

        if (!$user || !Hash::check($request->password, $user->password)) {
            return [
                'errors' => [
                    'email' => 'The provided credentials are incorrect'
                ]
            ];
        }

        $token = $user->createToken($user->name);

        return [
            'user' => $user,
            'token' => $token->plainTextToken
        ];
    }


    public function logout(Request $request)
    {   
        // deletes the userToken of the currently logged in user
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'You are logged out!'], 200);
        // return ['message' => 'You are logged out'];
    }
}
