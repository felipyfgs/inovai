<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\HasPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    use HasPagination;

    public function index(Request $request): JsonResponse
    {
        $query = User::with('roles')
            ->whereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->whereNull('office_id');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'ilike', "%{$request->search}%")
                    ->orWhere('email', 'ilike', "%{$request->search}%");
            });
        }

        return response()->json($this->paginate($query, $request, 15));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'office_id' => null,
            'is_active' => true,
            'must_change_password' => true,
        ]);

        $user->assignRole('admin');

        return response()->json($user->load('roles'), 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        if (! $user->hasRole('admin') || $user->office_id !== null) {
            return response()->json(['message' => 'Usuário não encontrado.'], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user->update($validated);

        return response()->json($user->load('roles'));
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Não é possível excluir a si mesmo.'], 422);
        }

        if (! $user->hasRole('admin') || $user->office_id !== null) {
            return response()->json(['message' => 'Usuário não encontrado.'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'Administrador removido com sucesso.']);
    }
}
