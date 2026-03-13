<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Handle user registration
     */
    public function register(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|confirmed'
        ]);

        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => Hash::make($fields['password']),
        ]);

        $token = $user->createToken('finflowtoken')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ], 201);
    }

    /**
     * Handle user login
     */
    public function login(Request $request)
    {
        // 1. Validate incoming request data
        $fields = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        // 2. Search for the user by email
        $user = User::where('email', $fields['email'])->first();

        // 3. Verify user exists and check password hash
        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password'
            ], 401);
        }

        // 4. Generate a new API token
        $token = $user->createToken('finflowtoken')->plainTextToken;

        // 5. Return user data and token to the frontend
        return response()->json([
            'user' => $user,
            'token' => $token
        ], 200);
    }

    /**
     * Handle user logout
     */
    public function logout(Request $request)
    {
        // Revoke the token that was used for the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
