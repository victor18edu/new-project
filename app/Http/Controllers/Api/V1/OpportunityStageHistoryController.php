<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\StoreOpportunityStageHistoryRequest;
use App\Http\Requests\V1\UpdateOpportunityStageHistoryRequest;
use App\Models\OpportunityStageHistory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;

class OpportunityStageHistoryController extends BaseApiController
{
    public function index()
    {
        Gate::authorize('viewAny', OpportunityStageHistory::class);

        $histories = OpportunityStageHistory::paginate();

        return $this->paginatedResponse($histories);
    }

    public function show(int $id)
    {
        $history = OpportunityStageHistory::find($id);

        if (! $history) {
            return $this->errorResponse(
                message: 'Histórico de estágio não encontrado.',
                status: 404
            );
        }

        Gate::authorize('view', $history);

        return $this->successResponse(
            message: 'Histórico de estágio encontrado.',
            data: $history,
            status: 200
        );
    }

    public function store(StoreOpportunityStageHistoryRequest $request)
    {
        Gate::authorize('create', OpportunityStageHistory::class);

        $validated = $request->validated();

        $history = OpportunityStageHistory::create($validated);

        return $this->successResponse(
            message: 'Histórico de estágio criado com sucesso.',
            data: $history,
            status: 201
        );
    }

    public function update(UpdateOpportunityStageHistoryRequest $request, int $id)
    {
        $history = OpportunityStageHistory::find($id);

        if (! $history) {
            return $this->errorResponse(
                message: 'Histórico de estágio não encontrado.',
                status: 404
            );
        }

        Gate::authorize('update', $history);

        $validated = $request->validated();

        $history->update($validated);

        return $this->successResponse(
            message: 'Histórico de estágio atualizado com sucesso.',
            data: $history
        );
    }

    public function destroy(int $id)
    {
        $history = OpportunityStageHistory::find($id);

        if (! $history) {
            return $this->errorResponse(
                message: 'Histórico de estágio não encontrado.',
                status: 404
            );
        }

        Gate::authorize('delete', $history);

        $history->delete();

        return $this->successResponse(
            message: 'Histórico de estágio removido com sucesso.',
            status: 200
        );
    }
}