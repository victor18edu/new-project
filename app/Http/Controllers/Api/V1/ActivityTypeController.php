<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\StoreActivityTypeRequest;
use App\Http\Requests\V1\UpdateActivityTypeRequest;
use App\Models\Activity;
use App\Models\ActivityType;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;

class ActivityTypeController extends BaseApiController
{
    public function index()
    {
        Gate::authorize('viewAny', ActivityType::class);

        $activityTypes = ActivityType::paginate();

        return $this->paginatedResponse($activityTypes);
    }

    public function show(int $id)
    {
        $activityType = ActivityType::find($id);

        if (! $activityType) {
            return $this->errorResponse(
                message: 'Tipo de atividade não encontrado.',
                status: 404
            );
        }

        Gate::authorize('view', $activityType);

        return $this->successResponse(
            message: 'Tipo de atividade encontrado.',
            data: $activityType,
            status: 200
        );
    }

    public function store(StoreActivityTypeRequest $request)
    {
        Gate::authorize('create', ActivityType::class);

        $validated = $request->validated();

        $activityType = ActivityType::create($validated);

        return $this->successResponse(
            message: 'Tipo de atividade criado com sucesso.',
            data: $activityType,
            status: 201
        );
    }

    public function update(UpdateActivityTypeRequest $request, int $id)
    {
        $activityType = ActivityType::find($id);

        if (! $activityType) {
            return $this->errorResponse(
                message: 'Tipo de atividade não encontrado.',
                status: 404
            );
        }

        Gate::authorize('update', $activityType);

        $validated = $request->validated();

        $activityType->update($validated);

        return $this->successResponse(
            message: 'Tipo de atividade atualizado com sucesso.',
            data: $activityType
        );
    }

    public function destroy(int $id)
    {
        $activityType = ActivityType::find($id);

        if (! $activityType) {
            return $this->errorResponse(
                message: 'Tipo de atividade não encontrado.',
                status: 404
            );
        }

        Gate::authorize('delete', $activityType);

        if (Activity::withTrashed()->where('activity_type_id', $id)->exists()) {
            return $this->errorResponse(
                message: 'Não é possível remover este tipo de atividade porque existem atividades vinculadas.',
                status: 409
            );
        }

        try {
            $activityType->delete();
        } catch (QueryException) {
            return $this->errorResponse(
                message: 'Não é possível remover este tipo de atividade porque existem atividades vinculadas.',
                status: 409
            );
        }

        return $this->successResponse(
            message: 'Tipo de atividade removido com sucesso.',
            status: 200
        );
    }
}