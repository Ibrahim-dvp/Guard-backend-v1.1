<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid credentials',
                'message' => 'The provided credentials do not match our records.',
            ], 401);
        }

        $user = User::with(['roles', 'organization'])->find(Auth::id());
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'message' => 'Login successful',
            'data' => new UserResource($user),
        ]);
    }

    public function user(Request $request)
    {
        $user = User::with(['roles', 'organization'])->find(Auth::id());
        
        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Logout successful',
        ]);
    }
}
