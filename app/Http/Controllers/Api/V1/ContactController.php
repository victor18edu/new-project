<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\StoreContactRequest;
use App\Http\Requests\V1\UpdateContactRequest;
use App\Models\Contact;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;

class ContactController extends BaseApiController
{
    public function index()
    {
        Gate::authorize('viewAny', Contact::class);

        $contacts = Contact::paginate();

        return $this->paginatedResponse($contacts);
    }

    public function show(int $id)
    {
        $contact = Contact::find($id);

        if (! $contact) {
            return $this->errorResponse(
                message: 'Contato não encontrado.',
                status: 404
            );
        }

        Gate::authorize('view', $contact);

        return $this->successResponse(
            message: 'Contato encontrado.',
            data: $contact,
            status: 200
        );
    }

    public function store(StoreContactRequest $request)
    {
        Gate::authorize('create', Contact::class);

        $validated = $request->validated();

        $contact = Contact::create($validated);

        return $this->successResponse(
            message: 'Contato criado com sucesso.',
            data: $contact,
            status: 201
        );
    }

    public function update(UpdateContactRequest $request, int $id)
    {
        $contact = Contact::find($id);

        if (! $contact) {
            return $this->errorResponse(
                message: 'Contato não encontrado.',
                status: 404
            );
        }

        Gate::authorize('update', $contact);

        $validated = $request->validated();

        $contact->update($validated);

        return $this->successResponse(
            message: 'Contato atualizado com sucesso.',
            data: $contact
        );
    }

    public function destroy(int $id)
    {
        $contact = Contact::find($id);

        if (! $contact) {
            return $this->errorResponse(
                message: 'Contato não encontrado.',
                status: 404
            );
        }

        Gate::authorize('delete', $contact);

        $contact->delete();

        return $this->successResponse(
            message: 'Contato removido com sucesso.',
            status: 200
        );
    }
}