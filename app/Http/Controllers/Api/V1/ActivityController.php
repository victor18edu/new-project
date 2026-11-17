<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\StoreActivityRequest;
use App\Http\Requests\V1\UpdateActivityRequest;
use App\Models\Activity;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;

class ActivityController extends BaseApiController
{
    public function index()
    {
        Gate::authorize('viewAny', Activity::class);

        $activities = Activity::paginate();

        return $this->paginatedResponse($activities);
    }

    public function show(int $id)
    {
        $activity = Activity::find($id);

        if (! $activity) {
            return $this->errorResponse(
                message: 'Atividade não encontrada.',
                status: 404
            );
        }

        Gate::authorize('view', $activity);

        return $this->successResponse(
            message: 'Atividade encontrada.',
            data: $activity,
            status: 200
        );
    }

    public function store(StoreActivityRequest $request)
    {
        Gate::authorize('create', Activity::class);

        $validated = $request->validated();
        $validated['assigned_to'] ??= $request->user()->id;
        $validated['created_by'] ??= $request->user()->id;
        $validated['due_date'] ??= now();

        $activity = Activity::create($validated);

        return $this->successResponse(
            message: 'Atividade criada com sucesso.',
            data: $activity,
            status: 201
        );
    }

    public function update(UpdateActivityRequest $request, int $id)
    {
        $activity = Activity::find($id);

        if (! $activity) {
            return $this->errorResponse(
                message: 'Atividade não encontrada.',
                status: 404
            );
        }

        Gate::authorize('update', $activity);

        $validated = $request->validated();

        $activity->update($validated);

        return $this->successResponse(
            message: 'Atividade atualizada com sucesso.',
            data: $activity
        );
    }

    public function destroy(int $id)
    {
        $activity = Activity::find($id);

        if (! $activity) {
            return $this->errorResponse(
                message: 'Atividade não encontrada.',
                status: 404
            );
        }

        Gate::authorize('delete', $activity);

        $activity->delete();

        return $this->successResponse(
            message: 'Atividade removida com sucesso.',
            status: 200
        );
    }
}