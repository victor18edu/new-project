<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\StoreProposalRequest;
use App\Http\Requests\V1\UpdateProposalRequest;
use App\Models\Proposal;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;

class ProposalController extends BaseApiController
{
    public function index()
    {
        Gate::authorize('viewAny', Proposal::class);

        $proposals = Proposal::paginate();

        return $this->paginatedResponse($proposals);
    }

    public function show(int $id)
    {
        $proposal = Proposal::find($id);

        if (! $proposal) {
            return $this->errorResponse(
                message: 'Proposta não encontrada.',
                status: 404
            );
        }

        Gate::authorize('view', $proposal);

        return $this->successResponse(
            message: 'Proposta encontrada.',
            data: $proposal,
            status: 200
        );
    }

    public function store(StoreProposalRequest $request)
    {
        Gate::authorize('create', Proposal::class);

        $validated = $request->validated();

        $proposal = Proposal::create($validated);

        return $this->successResponse(
            message: 'Proposta criada com sucesso.',
            data: $proposal,
            status: 201
        );
    }

    public function update(UpdateProposalRequest $request, int $id)
    {
        $proposal = Proposal::find($id);

        if (! $proposal) {
            return $this->errorResponse(
                message: 'Proposta não encontrada.',
                status: 404
            );
        }

        Gate::authorize('update', $proposal);

        $validated = $request->validated();

        $proposal->update($validated);

        return $this->successResponse(
            message: 'Proposta atualizada com sucesso.',
            data: $proposal
        );
    }

    public function destroy(int $id)
    {
        $proposal = Proposal::find($id);

        if (! $proposal) {
            return $this->errorResponse(
                message: 'Proposta não encontrada.',
                status: 404
            );
        }

        Gate::authorize('delete', $proposal);

        $proposal->delete();

        return $this->successResponse(
            message: 'Proposta removida com sucesso.',
            status: 200
        );
    }
}