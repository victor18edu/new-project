<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\SendWhatsAppMessage;
use App\Http\Requests\V1\SendWhatsAppMessageRequest;
use App\Models\Contact;
use Illuminate\Support\Facades\Gate;

class WhatsAppMessageController extends BaseApiController
{
    public function index(Contact $contact)
    {
        Gate::authorize('view', $contact);

        $messages = $contact->whatsappMessages()
            ->with('sender:id,name')
            ->latest('created_at')
            ->latest('id')
            ->paginate(20);

        return $this->paginatedResponse($messages);
    }

    public function store(SendWhatsAppMessageRequest $request, Contact $contact, SendWhatsAppMessage $action)
    {
        $message = $action->handle($contact, $request->validated()['body'], $request->user());
        $message->refresh();

        return $this->successResponse(
            message: 'Mensagem enfileirada para envio.',
            data: $message,
            status: 201
        );
    }
}
