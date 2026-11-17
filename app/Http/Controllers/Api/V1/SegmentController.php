<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\StoreSegmentRequest;
use App\Http\Requests\V1\UpdateSegmentRequest;
use App\Models\Company;
use App\Models\Segment;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;

class SegmentController extends BaseApiController
{
    public function index()
    {
        Gate::authorize('viewAny', Segment::class);

        $segments = Segment::paginate();

        return $this->paginatedResponse($segments);
    }

    public function show(int $id)
    {
        $segment = Segment::find($id);

        if (! $segment) {
            return $this->errorResponse(
                message: 'Segmento não encontrado.',
                status: 404
            );
        }

        Gate::authorize('view', $segment);

        return $this->successResponse(
            message: 'Segmento encontrado.',
            data: $segment,
            status: 200
        );
    }

    public function store(StoreSegmentRequest $request)
    {
        Gate::authorize('create', Segment::class);

        $validated = $request->validated();

        $segment = Segment::create($validated);

        return $this->successResponse(
            message: 'Segmento criado com sucesso.',
            data: $segment,
            status: 201
        );
    }

    public function update(UpdateSegmentRequest $request, int $id)
    {
        $segment = Segment::find($id);

        if (! $segment) {
            return $this->errorResponse(
                message: 'Segmento não encontrado.',
                status: 404
            );
        }

        Gate::authorize('update', $segment);

        $validated = $request->validated();

        $segment->update($validated);

        return $this->successResponse(
            message: 'Segmento atualizado com sucesso.',
            data: $segment
        );
    }

    public function destroy(int $id)
    {
        $segment = Segment::find($id);

        if (! $segment) {
            return $this->errorResponse(
                message: 'Segmento não encontrado.',
                status: 404
            );
        }

        Gate::authorize('delete', $segment);

        if (Company::withTrashed()->where('segment_id', $id)->exists()) {
            return $this->errorResponse(
                message: 'Não é possível remover este segmento porque existem empresas vinculadas.',
                status: 409
            );
        }

        try {
            $segment->delete();
        } catch (QueryException) {
            return $this->errorResponse(
                message: 'Não é possível remover este segmento porque existem empresas vinculadas.',
                status: 409
            );
        }

        return $this->successResponse(
            message: 'Segmento removido com sucesso.',
            status: 200
        );
    }
}