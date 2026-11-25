<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\StoreCustomerCategoryRequest;
use App\Http\Requests\V1\UpdateCustomerCategoryRequest;
use App\Models\Contact;
use App\Models\CustomerCategory;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;

class CustomerCategoryController extends BaseApiController
{
    public function index()
    {
        Gate::authorize('viewAny', CustomerCategory::class);

        return $this->paginatedResponse(CustomerCategory::orderBy('name')->paginate());
    }

    public function show(int $id)
    {
        $category = CustomerCategory::find($id);

        if ($category === null) {
            return $this->errorResponse(message: 'Categoria de cliente não encontrada.', status: 404);
        }

        Gate::authorize('view', $category);

        return $this->successResponse(
            message: 'Categoria de cliente encontrada.',
            data: $category
        );
    }

    public function store(StoreCustomerCategoryRequest $request)
    {
        Gate::authorize('create', CustomerCategory::class);

        $category = CustomerCategory::create($request->validated());

        return $this->successResponse(
            message: 'Categoria de cliente criada com sucesso.',
            data: $category,
            status: 201
        );
    }

    public function update(UpdateCustomerCategoryRequest $request, int $id)
    {
        $category = CustomerCategory::find($id);

        if ($category === null) {
            return $this->errorResponse(message: 'Categoria de cliente não encontrada.', status: 404);
        }

        Gate::authorize('update', $category);

        $category->update($request->validated());

        return $this->successResponse(
            message: 'Categoria de cliente atualizada com sucesso.',
            data: $category
        );
    }

    public function destroy(int $id)
    {
        $category = CustomerCategory::find($id);

        if ($category === null) {
            return $this->errorResponse(message: 'Categoria de cliente não encontrada.', status: 404);
        }

        Gate::authorize('delete', $category);

        if (Contact::withTrashed()->where('customer_category_id', $id)->exists()) {
            return $this->errorResponse(
                message: 'Não é possível remover esta categoria porque existem contatos vinculados.',
                status: 409
            );
        }

        try {
            $category->delete();
        } catch (QueryException) {
            return $this->errorResponse(
                message: 'Não é possível remover esta categoria porque existem contatos vinculados.',
                status: 409
            );
        }

        return $this->successResponse(message: 'Categoria de cliente removida com sucesso.');
    }
}
