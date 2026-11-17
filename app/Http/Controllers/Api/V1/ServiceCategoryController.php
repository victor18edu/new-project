<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\StoreServiceCategoryRequest;
use App\Http\Requests\V1\UpdateServiceCategoryRequest;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;

class ServiceCategoryController extends BaseApiController
{
    public function index()
    {
        Gate::authorize('viewAny', ServiceCategory::class);

        $serviceCategories = ServiceCategory::paginate();

        return $this->paginatedResponse($serviceCategories);
    }

    public function show(int $id)
    {
        $serviceCategory = ServiceCategory::find($id);

        if (! $serviceCategory) {
            return $this->errorResponse(
                message: 'Categoria de serviço não encontrada.',
                status: 404
            );
        }

        Gate::authorize('view', $serviceCategory);

        return $this->successResponse(
            message: 'Categoria de serviço encontrada.',
            data: $serviceCategory,
            status: 200
        );
    }

    public function store(StoreServiceCategoryRequest $request)
    {
        Gate::authorize('create', ServiceCategory::class);

        $validated = $request->validated();

        $serviceCategory = ServiceCategory::create($validated);

        return $this->successResponse(
            message: 'Categoria de serviço criada com sucesso.',
            data: $serviceCategory,
            status: 201
        );
    }

    public function update(UpdateServiceCategoryRequest $request, int $id)
    {
        $serviceCategory = ServiceCategory::find($id);

        if (! $serviceCategory) {
            return $this->errorResponse(
                message: 'Categoria de serviço não encontrada.',
                status: 404
            );
        }

        Gate::authorize('update', $serviceCategory);

        $validated = $request->validated();

        $serviceCategory->update($validated);

        return $this->successResponse(
            message: 'Categoria de serviço atualizada com sucesso.',
            data: $serviceCategory
        );
    }

    public function destroy(int $id)
    {
        $serviceCategory = ServiceCategory::find($id);

        if (! $serviceCategory) {
            return $this->errorResponse(
                message: 'Categoria de serviço não encontrada.',
                status: 404
            );
        }

        Gate::authorize('delete', $serviceCategory);

        if (Service::where('category_id', $id)->exists()) {
            return $this->errorResponse(
                message: 'Não é possível remover esta categoria porque existem serviços vinculados.',
                status: 409
            );
        }

        try {
            $serviceCategory->delete();
        } catch (QueryException) {
            return $this->errorResponse(
                message: 'Não é possível remover esta categoria porque existem serviços vinculados.',
                status: 409
            );
        }

        return $this->successResponse(
            message: 'Categoria de serviço removida com sucesso.',
            status: 200
        );
    }
}