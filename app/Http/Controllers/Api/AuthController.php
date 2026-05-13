<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


class AuthController extends Controller
{
    /* LOGIN */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required'
        ]);

        if (!Auth::attempt($request->only('email','password'))) {
            return response()->json(['message'=>'Login gagal'],401);
        }

        $user = Auth::user();

        // 🔥 hapus token lama
        $user->tokens()->delete();

        // 🔑 buat token baru
        $token = $user->createToken('token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user
        ]);
    }

    /* REGISTER */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'role' => 'required|in:Driver,Employee,Supervisor',
            'birthdate' => 'required|date',
            'phone' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6'
        ]);

        $user = \App\Models\User::create([
            'name' => $request->name,
            'role' => $request->role,
            'birthdate' => $request->birthdate,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        // 🔑 langsung kasih token (auto login setelah register)
        $token = $user->createToken('token')->plainTextToken;

        return response()->json([
            'message' => 'Register berhasil',
            'token' => $token,
            'user' => $user
        ], 201);
    }
}
