<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\StoreUserRequest;
use App\Http\Requests\V1\UpdateUserRequest;
use App\Models\User;
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

        $user->delete();

        return $this->successResponse(
            message: 'Usuário removido com sucesso.',
            status: 200
        );
    }
}