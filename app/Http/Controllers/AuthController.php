<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Login dan dapatkan JWT token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('nik', 'password');

        if (! $token = Auth::attempt($credentials)) {
            return response()->json([
                'status' => false,
                'message' => 'NIK atau password salah',
            ], 401);
        }

        $ttl = Auth::factory()->getTTL();

        return response()->json([
            'status' => true,
            'message' => 'Login berhasil',
            'data' => [
                'token_type' => 'bearer',
                'expires_in' => $ttl * 60,
            ],
        ])->cookie(
            'token', 
            $token, 
            $ttl, 
            '/', 
            null, 
            env('APP_ENV') !== 'local', // secure
            true, // httpOnly
            false, 
            'Lax' // sameSite
        );
    }

    /**
     * Refresh JWT token.
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = Auth::refresh();
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token tidak valid atau sudah kedaluwarsa',
            ], 401);
        }

        $ttl = Auth::factory()->getTTL();

        return response()->json([
            'status' => true,
            'message' => 'Token berhasil diperbarui',
            'data' => [
                'token_type' => 'bearer',
                'expires_in' => $ttl * 60,
            ],
        ])->cookie(
            'token', 
            $token, 
            $ttl, 
            '/', 
            null, 
            env('APP_ENV') !== 'local', // secure
            true, // httpOnly
            false, 
            'Lax' // sameSite
        );
    }

    /**
     * Logout dan invalidate token.
     */
    public function logout(): JsonResponse
    {
        Auth::logout();

        return response()->json([
            'status' => true,
            'message' => 'Logout berhasil',
        ])->withoutCookie('token');
    }
}
