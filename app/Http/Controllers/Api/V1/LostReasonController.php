<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\StoreLostReasonRequest;
use App\Http\Requests\V1\UpdateLostReasonRequest;
use App\Models\LostReason;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;

class LostReasonController extends BaseApiController
{
    public function index()
    {
        Gate::authorize('viewAny', LostReason::class);

        $lostReasons = LostReason::paginate();

        return $this->paginatedResponse($lostReasons);
    }

    public function show(int $id)
    {
        $lostReason = LostReason::find($id);

        if (! $lostReason) {
            return $this->errorResponse(
                message: 'Motivo de perda não encontrado.',
                status: 404
            );
        }

        Gate::authorize('view', $lostReason);

        return $this->successResponse(
            message: 'Motivo de perda encontrado.',
            data: $lostReason,
            status: 200
        );
    }

    public function store(StoreLostReasonRequest $request)
    {
        Gate::authorize('create', LostReason::class);

        $validated = $request->validated();

        $lostReason = LostReason::create($validated);

        return $this->successResponse(
            message: 'Motivo de perda criado com sucesso.',
            data: $lostReason,
            status: 201
        );
    }

    public function update(UpdateLostReasonRequest $request, int $id)
    {
        $lostReason = LostReason::find($id);

        if (! $lostReason) {
            return $this->errorResponse(
                message: 'Motivo de perda não encontrado.',
                status: 404
            );
        }

        Gate::authorize('update', $lostReason);

        $validated = $request->validated();

        $lostReason->update($validated);

        return $this->successResponse(
            message: 'Motivo de perda atualizado com sucesso.',
            data: $lostReason
        );
    }

    public function destroy(int $id)
    {
        $lostReason = LostReason::find($id);

        if (! $lostReason) {
            return $this->errorResponse(
                message: 'Motivo de perda não encontrado.',
                status: 404
            );
        }

        Gate::authorize('delete', $lostReason);

        $lostReason->delete();

        return $this->successResponse(
            message: 'Motivo de perda removido com sucesso.',
            status: 200
        );
    }
}