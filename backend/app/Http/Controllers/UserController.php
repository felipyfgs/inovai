<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * List users based on role
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = User::with(['office', 'roles', 'companies']);

        if ($user->hasRole('admin')) {
            // Admin sees all users
            if ($request->filled('office_id')) {
                $query->where('office_id', $request->office_id);
            }
        } elseif ($user->hasAnyRole(['office_user', 'accountant'])) {
            // Office user sees only users from their office
            $query->where('office_id', $user->office_id);
        } else {
            // Company users cannot list users
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'ilike', "%{$request->search}%")
                    ->orWhere('email', 'ilike', "%{$request->search}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $request->role));
        }

        $users = $query->orderBy('name')->paginate($request->get('per_page', 50));

        return response()->json($users);
    }

    /**
     * Create a new user
     */
    public function store(Request $request): JsonResponse
    {
        $currentUser = $request->user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ];

        if ($currentUser->hasRole('admin')) {
            $rules['office_id'] = ['required', 'exists:offices,id'];
            $rules['role'] = ['required', 'string', 'in:admin,office_user,accountant,company_user'];
        } else {
            // office_user can only create company_user in their own office
            $rules['role'] = ['required', 'string', 'in:company_user'];
        }

        $validated = $request->validate($rules);

        $officeId = $currentUser->hasRole('admin')
            ? $validated['office_id']
            : $currentUser->office_id;

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'office_id' => $officeId,
            'is_active' => $validated['is_active'] ?? true,
            'must_change_password' => $validated['role'] === 'company_user',
        ]);

        $user->assignRole($validated['role']);

        // If company_user, attach companies
        if ($validated['role'] === 'company_user' && $request->filled('company_ids')) {
            $this->authorizeCompanies($currentUser, $request->company_ids);
            $user->companies()->attach($request->company_ids);
        }

        return response()->json($user->load(['office', 'roles', 'companies']), 201);
    }

    /**
     * Show a user
     */
    public function show(Request $request, User $user): JsonResponse
    {
        $this->authorizeUser($request, $user);

        return response()->json($user->load(['office', 'roles', 'companies']));
    }

    /**
     * Update a user
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorizeUser($request, $user);

        $currentUser = $request->user();

        $rules = [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ];

        // Only admin can change role and office
        if ($currentUser->hasRole('admin')) {
            $rules['office_id'] = ['sometimes', 'exists:offices,id'];
            $rules['role'] = ['sometimes', 'string', 'in:admin,office_user,accountant,company_user'];
        }

        $validated = $request->validate($rules);

        $user->update($validated);

        // Update role if provided (admin only)
        if ($currentUser->hasRole('admin') && isset($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        return response()->json($user->load(['office', 'roles', 'companies']));
    }

    /**
     * Delete a user
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorizeUser($request, $user);

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Não é possível excluir a si mesmo.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'Usuário removido com sucesso.']);
    }

    /**
     * Toggle user active status (block/unblock)
     */
    public function toggleActive(Request $request, User $user): JsonResponse
    {
        $currentUser = $request->user();

        // Platform admin can toggle any user
        if (! $currentUser->hasRole('admin')) {
            // Office user can only toggle users in their office
            if ($user->office_id !== $currentUser->office_id) {
                return response()->json(['message' => 'Sem permissão para alterar este usuário.'], 403);
            }
        }

        if ($user->id === $currentUser->id) {
            return response()->json(['message' => 'Não é possível bloquear a si mesmo.'], 422);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return response()->json([
            'message' => $user->is_active ? 'Usuário desbloqueado com sucesso.' : 'Usuário bloqueado com sucesso.',
            'is_active' => $user->is_active,
        ]);
    }

    /**
     * Update current user's profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,'.$request->user()->id],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $request->user()->update($validated);

        return response()->json($request->user()->load(['office.subscription.plan', 'companies', 'roles']));
    }

    /**
     * Update current user's password
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Senha atual incorreta.'], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        return response()->json(['message' => 'Senha alterada com sucesso.']);
    }

    /**
     * List companies for a user (company_user)
     */
    public function companies(Request $request, User $user): JsonResponse
    {
        $this->authorizeUser($request, $user);

        $companies = $user->companies()->with('office')->get();

        return response()->json($companies);
    }

    /**
     * Attach companies to a user
     */
    public function attachCompanies(Request $request, User $user): JsonResponse
    {
        $this->authorizeUser($request, $user);

        $request->validate([
            'company_ids' => ['required', 'array'],
            'company_ids.*' => ['required', 'exists:companies,id'],
        ]);

        $currentUser = $request->user();
        $this->authorizeCompanies($currentUser, $request->company_ids);

        $user->companies()->syncWithoutDetaching($request->company_ids);

        return response()->json(['message' => 'Empresas vinculadas com sucesso.']);
    }

    /**
     * Detach a company from a user
     */
    public function detachCompany(Request $request, User $user, Company $company): JsonResponse
    {
        $this->authorizeUser($request, $user);

        $user->companies()->detach($company->id);

        return response()->json(['message' => 'Empresa desvinculada com sucesso.']);
    }

    /**
     * Authorize access to a user
     */
    private function authorizeUser(Request $request, User $user): void
    {
        $currentUser = $request->user();

        if ($currentUser->hasRole('admin')) {
            return;
        }

        if ($currentUser->hasAnyRole(['office_user', 'accountant'])) {
            if ($user->office_id !== $currentUser->office_id) {
                abort(403, 'Sem permissão para acessar este usuário.');
            }

            return;
        }

        // company_user cannot manage users
        abort(403, 'Acesso negado.');
    }

    /**
     * Authorize access to companies
     */
    private function authorizeCompanies(User $user, array $companyIds): void
    {
        if ($user->hasRole('admin')) {
            return;
        }

        // office_user can only attach companies from their office
        $allowedCount = Company::whereIn('id', $companyIds)
            ->where('office_id', $user->office_id)
            ->count();

        if ($allowedCount !== count($companyIds)) {
            abort(403, 'Sem permissão para vincular estas empresas.');
        }
    }
}
