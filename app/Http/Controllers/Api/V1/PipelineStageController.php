<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\StorePipelineStageRequest;
use App\Http\Requests\V1\UpdatePipelineStageRequest;
use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
use App\Models\PipelineStage;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;

class PipelineStageController extends BaseApiController
{
    public function index()
    {
        Gate::authorize('viewAny', PipelineStage::class);

        $pipelineStages = PipelineStage::paginate();

        return $this->paginatedResponse($pipelineStages);
    }

    public function show(int $id)
    {
        $pipelineStage = PipelineStage::find($id);

        if (! $pipelineStage) {
            return $this->errorResponse(
                message: 'Estágio de pipeline não encontrado.',
                status: 404
            );
        }

        Gate::authorize('view', $pipelineStage);

        return $this->successResponse(
            message: 'Estágio de pipeline encontrado.',
            data: $pipelineStage,
            status: 200
        );
    }

    public function store(StorePipelineStageRequest $request)
    {
        Gate::authorize('create', PipelineStage::class);

        $validated = $request->validated();

        $pipelineStage = PipelineStage::create($validated);

        return $this->successResponse(
            message: 'Estágio de pipeline criado com sucesso.',
            data: $pipelineStage,
            status: 201
        );
    }

    public function update(UpdatePipelineStageRequest $request, int $id)
    {
        $pipelineStage = PipelineStage::find($id);

        if (! $pipelineStage) {
            return $this->errorResponse(
                message: 'Estágio de pipeline não encontrado.',
                status: 404
            );
        }

        Gate::authorize('update', $pipelineStage);

        $validated = $request->validated();

        $pipelineStage->update($validated);

        return $this->successResponse(
            message: 'Estágio de pipeline atualizado com sucesso.',
            data: $pipelineStage
        );
    }

    public function destroy(int $id)
    {
        $pipelineStage = PipelineStage::find($id);

        if (! $pipelineStage) {
            return $this->errorResponse(
                message: 'Estágio de pipeline não encontrado.',
                status: 404
            );
        }

        Gate::authorize('delete', $pipelineStage);

        $dependencies = [];

        if (Opportunity::withTrashed()->where('stage_id', $id)->exists()) {
            $dependencies[] = 'oportunidades';
        }

        if (OpportunityStageHistory::where('to_stage_id', $id)->exists()) {
            $dependencies[] = 'históricos de estágio';
        }

        if ($dependencies !== []) {
            return $this->errorResponse(
                message: 'Não é possível remover este estágio porque existem registros vinculados: ' . implode(', ', $dependencies) . '.',
                status: 409
            );
        }

        try {
            $pipelineStage->delete();
        } catch (QueryException) {
            return $this->errorResponse(
                message: 'Não é possível remover este estágio porque existem registros vinculados.',
                status: 409
            );
        }

        return $this->successResponse(
            message: 'Estágio de pipeline removido com sucesso.',
            status: 200
        );
    }
}