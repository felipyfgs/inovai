<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'office_name' => ['required', 'string', 'max:255'],
            'office_cnpj' => ['nullable', 'string', 'max:18'],
        ]);

        $result = DB::transaction(function () use ($validated) {
            $office = Office::create([
                'name' => $validated['office_name'],
                'cnpj' => $validated['office_cnpj'] ?? null,
                'email' => $validated['email'],
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'office_id' => $office->id,
            ]);

            $user->assignRole('accountant');

            return $user;
        });

        $token = $result->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $result->load('office'),
            'token' => $token,
        ], 201);
    }
}
