<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\StoreLeadSourceRequest;
use App\Http\Requests\V1\UpdateLeadSourceRequest;
use App\Models\LeadSource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;

class LeadSourceController extends BaseApiController
{
    public function index()
    {
        Gate::authorize('viewAny', LeadSource::class);

        $leadSources = LeadSource::paginate();

        return $this->paginatedResponse($leadSources);
    }

    public function show(int $id)
    {
        $leadSource = LeadSource::find($id);

        if (! $leadSource) {
            return $this->errorResponse(
                message: 'Fonte de lead não encontrada.',
                status: 404
            );
        }

        Gate::authorize('view', $leadSource);

        return $this->successResponse(
            message: 'Fonte de lead encontrada.',
            data: $leadSource,
            status: 200
        );
    }

    public function store(StoreLeadSourceRequest $request)
    {
        Gate::authorize('create', LeadSource::class);

        $validated = $request->validated();

        $leadSource = LeadSource::create($validated);

        return $this->successResponse(
            message: 'Fonte de lead criada com sucesso.',
            data: $leadSource,
            status: 201
        );
    }

    public function update(UpdateLeadSourceRequest $request, int $id)
    {
        $leadSource = LeadSource::find($id);

        if (! $leadSource) {
            return $this->errorResponse(
                message: 'Fonte de lead não encontrada.',
                status: 404
            );
        }

        Gate::authorize('update', $leadSource);

        $validated = $request->validated();

        $leadSource->update($validated);

        return $this->successResponse(
            message: 'Fonte de lead atualizada com sucesso.',
            data: $leadSource
        );
    }

    public function destroy(int $id)
    {
        $leadSource = LeadSource::find($id);

        if (! $leadSource) {
            return $this->errorResponse(
                message: 'Fonte de lead não encontrada.',
                status: 404
            );
        }

        Gate::authorize('delete', $leadSource);

        $leadSource->delete();

        return $this->successResponse(
            message: 'Fonte de lead removida com sucesso.',
            status: 200
        );
    }
}