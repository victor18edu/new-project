<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\StoreServiceRequest;
use App\Http\Requests\V1\UpdateServiceRequest;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;

class ServiceController extends BaseApiController
{
    public function index()
    {
        Gate::authorize('viewAny', Service::class);

        $services = Service::paginate();

        return $this->paginatedResponse($services);
    }

    public function show(int $id)
    {
        $service = Service::find($id);

        if (! $service) {
            return $this->errorResponse(
                message: 'Serviço não encontrado.',
                status: 404
            );
        }

        Gate::authorize('view', $service);

        return $this->successResponse(
            message: 'Serviço encontrado.',
            data: $service,
            status: 200
        );
    }

    public function store(StoreServiceRequest $request)
    {
        Gate::authorize('create', Service::class);

        $validated = $request->validated();
        $validated['slug'] ??= Str::slug($validated['name']);

        $service = DB::transaction(function () use ($validated) {
            $validated['category_id'] ??= ServiceCategory::firstOrCreate(
                ['slug' => 'geral'],
                ['name' => 'Geral', 'is_active' => true],
            )->id;

            return Service::create($validated);
        });

        return $this->successResponse(
            message: 'Serviço criado com sucesso.',
            data: $service,
            status: 201
        );
    }

    public function update(UpdateServiceRequest $request, int $id)
    {
        $service = Service::find($id);

        if (! $service) {
            return $this->errorResponse(
                message: 'Serviço não encontrado.',
                status: 404
            );
        }

        Gate::authorize('update', $service);

        $validated = $request->validated();

        $service->update($validated);

        return $this->successResponse(
            message: 'Serviço atualizado com sucesso.',
            data: $service
        );
    }

    public function destroy(int $id)
    {
        $service = Service::find($id);

        if (! $service) {
            return $this->errorResponse(
                message: 'Serviço não encontrado.',
                status: 404
            );
        }

        Gate::authorize('delete', $service);

        $service->delete();

        return $this->successResponse(
            message: 'Serviço removido com sucesso.',
            status: 200
        );
    }
}