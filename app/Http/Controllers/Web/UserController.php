<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreUserRequest;
use App\Http\Requests\Web\UpdateUserRequest;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
use App\Models\Proposal;
use App\Models\User;
use App\Support\FormLimits;
use App\Support\OperatorRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $search = trim((string) $request->query('q', ''));
        $role = (string) $request->query('role', '');
        $status = (string) $request->query('status', '');

        $users = User::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(in_array($role, array_keys(OperatorRole::labels()), true), fn ($query) => $query->where('role', $role))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'roles' => OperatorRole::labels(),
            'protectedUserId' => User::PROTECTED_ID,
            'filters' => [
                'q' => $search,
                'role' => $role,
                'status' => $status,
            ],
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', User::class);

        return view('users.create', [
            'roles' => OperatorRole::labels(),
            'limits' => FormLimits::from($request, StoreUserRequest::class),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        Gate::authorize('create', User::class);

        User::create($request->validated());

        return redirect()->route('web.users.index')
            ->with('status', 'Usuário criado com sucesso.');
    }

    public function show(User $user): View
    {
        Gate::authorize('view', $user);

        return view('users.show', [
            'user' => $user,
            'roles' => OperatorRole::labels(),
            'protectedUserId' => User::PROTECTED_ID,
        ]);
    }

    public function edit(Request $request, User $user): View
    {
        Gate::authorize('update', $user);

        return view('users.edit', [
            'user' => $user,
            'roles' => OperatorRole::labels(),
            'protectedUserId' => User::PROTECTED_ID,
            'limits' => FormLimits::from($request, UpdateUserRequest::class),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $validated = $request->validated();

        if (array_key_exists('password', $validated) && $validated['password'] === null) {
            unset($validated['password']);
        }

        if ($user->id === User::PROTECTED_ID) {
            $validated = array_intersect_key($validated, array_flip(['name', 'email', 'password']));
        }

        $user->update($validated);

        return redirect()->route('web.users.index')
            ->with('status', 'Usuário atualizado com sucesso.');
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        if ($user->id === User::PROTECTED_ID) {
            return redirect()->route('web.users.index')
                ->with('error', 'O usuário principal do sistema não pode ser removido.');
        }

        $dependencies = [];

        if (Company::withTrashed()->where('owner_id', $user->id)->exists()) {
            $dependencies[] = 'empresas';
        }

        if (Opportunity::withTrashed()->where('owner_id', $user->id)->exists()) {
            $dependencies[] = 'oportunidades';
        }

        if (Proposal::withTrashed()->where('owner_id', $user->id)->exists()) {
            $dependencies[] = 'propostas';
        }

        if (Activity::withTrashed()->where('assigned_to', $user->id)->exists()
            || Activity::withTrashed()->where('created_by', $user->id)->exists()) {
            $dependencies[] = 'atividades';
        }

        if (OpportunityStageHistory::where('user_id', $user->id)->exists()) {
            $dependencies[] = 'históricos de estágio';
        }

        if ($dependencies !== []) {
            return redirect()->route('web.users.index')
                ->with('error', 'Não é possível remover este usuário porque existem registros vinculados: '.implode(', ', $dependencies).'.');
        }

        $user->delete();

        return redirect()->route('web.users.index')
            ->with('status', 'Usuário removido com sucesso.');
    }
}
