<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\StoreOpportunityRequest;
use App\Http\Requests\V1\UpdateOpportunityRequest;
use App\Models\Opportunity;
use App\Models\Proposal;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;

class OpportunityController extends BaseApiController
{
    public function index()
    {
        Gate::authorize('viewAny', Opportunity::class);

        $opportunities = Opportunity::paginate();

        return $this->paginatedResponse($opportunities);
    }

    public function show(int $id)
    {
        $opportunity = Opportunity::find($id);

        if (! $opportunity) {
            return $this->errorResponse(
                message: 'Oportunidade não encontrada.',
                status: 404
            );
        }

        Gate::authorize('view', $opportunity);

        return $this->successResponse(
            message: 'Oportunidade encontrada.',
            data: $opportunity,
            status: 200
        );
    }

    public function store(StoreOpportunityRequest $request)
    {
        Gate::authorize('create', Opportunity::class);

        $validated = $request->validated();
        $validated['owner_id'] ??= $request->user()->id;

        $opportunity = Opportunity::create($validated);

        return $this->successResponse(
            message: 'Oportunidade criada com sucesso.',
            data: $opportunity,
            status: 201
        );
    }

    public function update(UpdateOpportunityRequest $request, int $id)
    {
        $opportunity = Opportunity::find($id);

        if (! $opportunity) {
            return $this->errorResponse(
                message: 'Oportunidade não encontrada.',
                status: 404
            );
        }

        Gate::authorize('update', $opportunity);

        $validated = $request->validated();
        $fromStageId = $opportunity->stage_id;
        $stageChanged = array_key_exists('stage_id', $validated)
            && (int) $validated['stage_id'] !== (int) $fromStageId;

        DB::transaction(function () use ($opportunity, $validated, $fromStageId, $stageChanged, $request) {
            $opportunity->update($validated);

            if (! $stageChanged) {
                return;
            }

            $lastHistory = $opportunity->stageHistories()
                ->latest('created_at')
                ->first();

            $durationInSeconds = $lastHistory?->created_at
                ? $lastHistory->created_at->diffInSeconds(now())
                : 0;

            $opportunity->stageHistories()->create([
                'from_stage_id' => $fromStageId,
                'to_stage_id' => $opportunity->stage_id,
                'user_id' => $request->user()->id,
                'duration_in_seconds' => max(0, $durationInSeconds),
            ]);
        });

        return $this->successResponse(
            message: 'Oportunidade atualizada com sucesso.',
            data: $opportunity
        );
    }

    public function destroy(int $id)
    {
        $opportunity = Opportunity::find($id);

        if (! $opportunity) {
            return $this->errorResponse(
                message: 'Oportunidade não encontrada.',
                status: 404
            );
        }

        Gate::authorize('delete', $opportunity);

        if (Proposal::withTrashed()->where('opportunity_id', $id)->exists()) {
            return $this->errorResponse(
                message: 'Não é possível remover esta oportunidade porque existem propostas vinculadas.',
                status: 409
            );
        }

        try {
            $opportunity->delete();
        } catch (QueryException) {
            return $this->errorResponse(
                message: 'Não é possível remover esta oportunidade porque existem propostas vinculadas.',
                status: 409
            );
        }

        return $this->successResponse(
            message: 'Oportunidade removida com sucesso.',
            status: 200
        );
    }
}