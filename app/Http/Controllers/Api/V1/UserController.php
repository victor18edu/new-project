<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\StoreUserRequest;
use App\Http\Requests\V1\UpdateUserRequest;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;

class UserController extends BaseApiController
{
    public function index()
    {
        Gate::authorize('viewAny', User::class);

        $users = User::paginate();

        return $this->paginatedResponse($users);
    }

    public function show(int $id)
    {
        $user = User::find($id);

        if (! $user) {
            return $this->errorResponse(
                message: 'Usuário não encontrado.',
                status: 404
            );
        }

        Gate::authorize('view', $user);

        return $this->successResponse(
            message: 'Usuário encontrado.',
            data: $user,
            status: 200
        );
    }

    public function store(StoreUserRequest $request)
    {
        Gate::authorize('create', User::class);

        $validated = $request->validated();

        $user = User::create($validated);

        return $this->successResponse(
            message: 'Usuário criado com sucesso.',
            data: $user,
            status: 201
        );
    }

    public function update(UpdateUserRequest $request, int $id)
    {
        $user = User::find($id);

        if (! $user) {
            return $this->errorResponse(
                message: 'Usuário não encontrado.',
                status: 404
            );
        }

        Gate::authorize('update', $user);

        $validated = $request->validated();

        $user->update($validated);

        return $this->successResponse(
            message: 'Usuário atualizado com sucesso.',
            data: $user
        );
    }

    public function destroy(int $id)
    {
        $user = User::find($id);

        if (! $user) {
            return $this->errorResponse(
                message: 'Usuário não encontrado.',
                status: 404
            );
        }

        Gate::authorize('delete', $user);

        $dependencies = [];

        if (Company::withTrashed()->where('owner_id', $id)->exists()) {
            $dependencies[] = 'empresas';
        }

        if (Opportunity::withTrashed()->where('owner_id', $id)->exists()) {
            $dependencies[] = 'oportunidades';
        }

        if (Proposal::withTrashed()->where('owner_id', $id)->exists()) {
            $dependencies[] = 'propostas';
        }

        if (Activity::withTrashed()->where('assigned_to', $id)->exists()
            || Activity::withTrashed()->where('created_by', $id)->exists()) {
            $dependencies[] = 'atividades';
        }

        if (OpportunityStageHistory::where('user_id', $id)->exists()) {
            $dependencies[] = 'históricos de estágio';
        }

        if ($dependencies !== []) {
            return $this->errorResponse(
                message: 'Não é possível remover este usuário porque existem registros vinculados: ' . implode(', ', $dependencies) . '.',
                status: 409
            );
        }

        try {
            $user->delete();
        } catch (QueryException) {
            return $this->errorResponse(
                message: 'Não é possível remover este usuário porque existem registros vinculados.',
                status: 409
            );
        }

        return $this->successResponse(
            message: 'Usuário removido com sucesso.',
            status: 200
        );
    }
}