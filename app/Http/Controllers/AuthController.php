<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $role = $validated['role'] ?? 'estudiante';

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $user->syncRoles([$role]);

        $token = $user->createToken('auth_token');

        return response()->json([
            'message' => 'Usuario registrado exitosamente.',
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => $user->load('roles'),
        ], 201);
    }

    public function login(Request $request)
    {
        if (Auth::attempt($request->only('email', 'password'))) {
            /** @var User $user */
            $user = Auth::user();
            $token = $user->createToken('auth_token');
            $user->load('roles');

            return response()->json([
                'access_token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'user' => $user,
            ]);
        }

        return response()->json([
            'message' => 'Invalid credentials',
        ], 422);
    }

    public function logout(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function profile(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $user->load('roles');

        return response()->json([
            'user' => $user,
        ]);
    }
}
