<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\StorePipelineRequest;
use App\Http\Requests\V1\UpdatePipelineRequest;
use App\Models\Opportunity;
use App\Models\Pipeline;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;

class PipelineController extends BaseApiController
{
    public function index()
    {
        Gate::authorize('viewAny', Pipeline::class);

        $pipelines = Pipeline::paginate();

        return $this->paginatedResponse($pipelines);
    }

    public function show(int $id)
    {
        $pipeline = Pipeline::find($id);

        if (! $pipeline) {
            return $this->errorResponse(
                message: 'Pipeline não encontrado.',
                status: 404
            );
        }

        Gate::authorize('view', $pipeline);

        return $this->successResponse(
            message: 'Pipeline encontrado.',
            data: $pipeline,
            status: 200
        );
    }

    public function store(StorePipelineRequest $request)
    {
        Gate::authorize('create', Pipeline::class);

        $validated = $request->validated();

        $pipeline = Pipeline::create($validated);

        return $this->successResponse(
            message: 'Pipeline criado com sucesso.',
            data: $pipeline,
            status: 201
        );
    }

    public function update(UpdatePipelineRequest $request, int $id)
    {
        $pipeline = Pipeline::find($id);

        if (! $pipeline) {
            return $this->errorResponse(
                message: 'Pipeline não encontrado.',
                status: 404
            );
        }

        Gate::authorize('update', $pipeline);

        $validated = $request->validated();

        $pipeline->update($validated);

        return $this->successResponse(
            message: 'Pipeline atualizado com sucesso.',
            data: $pipeline
        );
    }

    public function destroy(int $id)
    {
        $pipeline = Pipeline::find($id);

        if (! $pipeline) {
            return $this->errorResponse(
                message: 'Pipeline não encontrado.',
                status: 404
            );
        }

        Gate::authorize('delete', $pipeline);

        if (Opportunity::withTrashed()->where('pipeline_id', $id)->exists()) {
            return $this->errorResponse(
                message: 'Não é possível remover este pipeline porque existem oportunidades vinculadas.',
                status: 409
            );
        }

        try {
            $pipeline->delete();
        } catch (QueryException) {
            return $this->errorResponse(
                message: 'Não é possível remover este pipeline porque existem oportunidades vinculadas.',
                status: 409
            );
        }

        return $this->successResponse(
            message: 'Pipeline removido com sucesso.',
            status: 200
        );
    }
}