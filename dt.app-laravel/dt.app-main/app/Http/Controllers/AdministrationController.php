<?php

namespace App\Http\Controllers;

use App\Concerns\PasswordValidationRules;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdministrationController extends Controller
{
    use PasswordValidationRules;

    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('administration.index', [
            'activeTab' => $request->query('tab', 'admin-roles'),
            'roles' => Role::query()
                ->withCount('users')
                ->when(
                    filled($request->string('role_search')->toString()),
                    fn ($query) => $query->where('name', 'like', '%'.$request->string('role_search')->toString().'%')
                )
                ->orderBy('name')
                ->paginate(3, ['*'], 'roles_page')
                ->withQueryString(),
            'rolesForSelect' => Role::query()
                ->orderBy('name')
                ->get(),
            'users' => User::query()
                ->with('role')
                ->when(
                    filled($request->string('user_search')->toString()),
                    function ($query) use ($request) {
                        $term = '%'.$request->string('user_search')->toString().'%';

                        $query->where(function ($builder) use ($term) {
                            $builder->where('name', 'like', $term)
                                ->orWhere('email', 'like', $term)
                                ->orWhereHas('role', fn ($roleQuery) => $roleQuery->where('name', 'like', $term));
                        });
                    }
                )
                ->orderBy('name')
                ->paginate(3, ['*'], 'users_page')
                ->withQueryString(),
        ]);
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
        ]);

        Role::query()->create([
            'name' => strtoupper(trim($validated['name'])),
        ]);

        return $this->redirectToAdministration($request, 'admin_success', 'Role cree avec succes.');
    }

    public function updateRole(Request $request, Role $role): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
        ]);

        $role->update([
            'name' => strtoupper(trim($validated['name'])),
        ]);

        return $this->redirectToAdministration($request, 'admin_success', 'Role modifie avec succes.');
    }

    public function destroyRole(Request $request, Role $role): RedirectResponse
    {
        $this->authorizeAdmin($request);

        if ($role->users()->exists()) {
            return $this->redirectToAdministration($request, 'admin_error', 'Impossible de supprimer un role deja attribue a des utilisateurs.');
        }

        $role->delete();

        return $this->redirectToAdministration($request, 'admin_success', 'Role supprime avec succes.');
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role_id' => ['required', 'exists:roles,id'],
            'password' => $this->passwordRules(),
        ]);

        User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role_id' => $validated['role_id'],
            'email_verified_at' => now(),
        ]);

        return $this->redirectToAdministration($request, 'admin_success', 'Utilisateur cree avec succes.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role_id' => ['required', 'exists:roles,id'],
            'password' => ['nullable', 'string', \Illuminate\Validation\Rules\Password::default(), 'confirmed'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role_id = (int) $validated['role_id'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return $this->redirectToAdministration($request, 'admin_success', 'Utilisateur modifie avec succes.');
    }

    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin($request);

        if ((int) $request->user()->id === (int) $user->id) {
            return $this->redirectToAdministration($request, 'admin_error', 'Vous ne pouvez pas supprimer votre propre compte depuis cet ecran.');
        }

        $user->delete();

        return $this->redirectToAdministration($request, 'admin_success', 'Utilisateur supprime avec succes.');
    }

    protected function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role?->name === 'ADMIN', 403);
    }

    protected function redirectToAdministration(Request $request, string $flashKey, string $message): RedirectResponse
    {
        return redirect()
            ->route('administration.index', ['tab' => $request->input('tab', 'admin-roles')])
            ->with($flashKey, $message);
    }
}
