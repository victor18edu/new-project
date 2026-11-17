<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\StoreCompanyRequest;
use App\Http\Requests\V1\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Proposal;
use App\Models\Segment;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;

class CompanyController extends BaseApiController
{
    public function index()
    {
        Gate::authorize('viewAny', Company::class);

        $companies = Company::paginate();

        return $this->paginatedResponse($companies);
    }

    public function show(int $id)
    {
        $company = Company::find($id);

        if (! $company) {
            return $this->errorResponse(
                message: 'Empresa não encontrada.',
                status: 404
            );
        }

        Gate::authorize('view', $company);

        return $this->successResponse(
            message: 'Empresa encontrada.',
            data: $company,
            status: 200
        );
    }

    public function store(StoreCompanyRequest $request)
    {
        Gate::authorize('create', Company::class);

        $validated = $request->validated();
        $validated['owner_id'] ??= $request->user()->id;
        $validated['city'] ??= 'São Paulo';
        $validated['state'] ??= 'SP';
        $validated['temperature'] ??= 'cold';

        $company = DB::transaction(function () use ($validated) {
            $validated['segment_id'] ??= Segment::firstOrCreate(
                ['slug' => 'geral'],
                ['name' => 'Geral', 'is_active' => true],
            )->id;

            return Company::create($validated);
        });

        return $this->successResponse(
            message: 'Empresa criada com sucesso.',
            data: $company,
            status: 201
        );
    }

    public function update(UpdateCompanyRequest $request, int $id)
    {
        $company = Company::find($id);

        if (! $company) {
            return $this->errorResponse(
                message: 'Empresa não encontrada.',
                status: 404
            );
        }

        Gate::authorize('update', $company);

        $validated = $request->validated();

        $company->update($validated);

        return $this->successResponse(
            message: 'Empresa atualizada com sucesso.',
            data: $company
        );
    }

    public function destroy(int $id)
    {
        $company = Company::find($id);

        if (! $company) {
            return $this->errorResponse(
                message: 'Empresa não encontrada.',
                status: 404
            );
        }

        Gate::authorize('delete', $company);

        $dependencies = [];

        if (Opportunity::withTrashed()->where('company_id', $id)->exists()) {
            $dependencies[] = 'oportunidades';
        }

        if (Proposal::withTrashed()->where('company_id', $id)->exists()) {
            $dependencies[] = 'propostas';
        }

        if ($dependencies !== []) {
            return $this->errorResponse(
                message: 'Não é possível remover esta empresa porque existem registros vinculados: ' . implode(', ', $dependencies) . '.',
                status: 409
            );
        }

        try {
            $company->delete();
        } catch (QueryException) {
            return $this->errorResponse(
                message: 'Não é possível remover esta empresa porque existem registros vinculados.',
                status: 409
            );
        }

        return $this->successResponse(
            message: 'Empresa removida com sucesso.',
            status: 200
        );
    }
}