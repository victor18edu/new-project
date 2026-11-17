<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\StoreProposalItemRequest;
use App\Http\Requests\V1\UpdateProposalItemRequest;
use App\Models\ProposalItem;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;

class ProposalItemController extends BaseApiController
{
    public function index()
    {
        Gate::authorize('viewAny', ProposalItem::class);

        $proposalItems = ProposalItem::paginate();

        return $this->paginatedResponse($proposalItems);
    }

    public function show(int $id)
    {
        $proposalItem = ProposalItem::find($id);

        if (! $proposalItem) {
            return $this->errorResponse(
                message: 'Item de proposta não encontrado.',
                status: 404
            );
        }

        Gate::authorize('view', $proposalItem);

        return $this->successResponse(
            message: 'Item de proposta encontrado.',
            data: $proposalItem,
            status: 200
        );
    }

    public function store(StoreProposalItemRequest $request)
    {
        Gate::authorize('create', ProposalItem::class);

        $validated = $request->validated();

        $proposalItem = ProposalItem::create($validated);

        return $this->successResponse(
            message: 'Item de proposta criado com sucesso.',
            data: $proposalItem,
            status: 201
        );
    }

    public function update(UpdateProposalItemRequest $request, int $id)
    {
        $proposalItem = ProposalItem::find($id);

        if (! $proposalItem) {
            return $this->errorResponse(
                message: 'Item de proposta não encontrado.',
                status: 404
            );
        }

        Gate::authorize('update', $proposalItem);

        $validated = $request->validated();

        $proposalItem->update($validated);

        return $this->successResponse(
            message: 'Item de proposta atualizado com sucesso.',
            data: $proposalItem
        );
    }

    public function destroy(int $id)
    {
        $proposalItem = ProposalItem::find($id);

        if (! $proposalItem) {
            return $this->errorResponse(
                message: 'Item de proposta não encontrado.',
                status: 404
            );
        }

        Gate::authorize('delete', $proposalItem);

        $proposalItem->delete();

        return $this->successResponse(
            message: 'Item de proposta removido com sucesso.',
            status: 200
        );
    }
}