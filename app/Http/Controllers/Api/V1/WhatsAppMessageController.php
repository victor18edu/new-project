<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\DeleteWhatsAppMessage;
use App\Actions\EditWhatsAppMessage;
use App\Actions\RetryWhatsAppMessage;
use App\Actions\SendWhatsAppMessage;
use App\Exceptions\WhatsAppRemoteEditFailed;
use App\Http\Requests\V1\SendWhatsAppMessageRequest;
use App\Http\Requests\V1\UpdateWhatsAppMessageRequest;
use App\Models\Contact;
use App\Models\WhatsAppMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WhatsAppMessageController extends BaseApiController
{
    public function index(Contact $contact)
    {
        Gate::authorize('view', $contact);

        $messages = $contact->whatsappMessages()
            ->with(['sender:id,name', 'editor:id,name'])
            ->latest('created_at')
            ->latest('id')
            ->paginate(20);

        return $this->paginatedResponse($messages);
    }

    public function store(SendWhatsAppMessageRequest $request, Contact $contact, SendWhatsAppMessage $action)
    {
        Gate::authorize('sendMessage', $contact);

        $message = $action->handle($contact, $request->validated()['body'], $request->user());
        $message->refresh();

        return $this->successResponse(
            message: 'Mensagem enfileirada para envio.',
            data: $message,
            status: 201
        );
    }

    public function update(
        UpdateWhatsAppMessageRequest $request,
        Contact $contact,
        WhatsAppMessage $message,
        EditWhatsAppMessage $action
    ) {
        abort_unless($message->contact_id === $contact->id, 404);

        Gate::authorize('update', $message);

        try {
            $edited = $action->handle($message, $request->validated()['body'], $request->user());
        } catch (WhatsAppRemoteEditFailed $exception) {
            return $this->errorResponse(message: $exception->getMessage(), status: 502);
        }

        $remoteEdit = data_get($edited->metadata, 'remote_edit');

        return $this->successResponse(
            message: match ($remoteEdit) {
                'edited' => 'Mensagem editada no WhatsApp.',
                default => 'Mensagem editada localmente; ainda não foi enviada.',
            },
            data: array_merge($edited->toArray(), ['remote_edit' => $remoteEdit]),
        );
    }

    public function retry(
        Contact $contact,
        WhatsAppMessage $message,
        RetryWhatsAppMessage $action
    ) {
        abort_unless($message->contact_id === $contact->id, 404);

        Gate::authorize('retry', $message);

        $retried = $action->handle($message);

        return $this->successResponse(message: 'Mensagem reenfileirada para envio.', data: $retried);
    }

    public function destroy(
        Request $request,
        Contact $contact,
        WhatsAppMessage $message,
        DeleteWhatsAppMessage $action
    ) {
        abort_unless($message->contact_id === $contact->id, 404);

        Gate::authorize('delete', $message);

        $remoteStatus = $action->handle($message, $request->user());

        if ($remoteStatus === 'failed') {
            return $this->errorResponse(
                message: DeleteWhatsAppMessage::FAILED_MESSAGE,
                data: ['remote_delete' => $remoteStatus],
                status: 502,
            );
        }

        return $this->successResponse(message: match ($remoteStatus) {
            'deleted' => 'Mensagem apagada da conversa e do WhatsApp.',
            default => 'Mensagem apagada da conversa.',
        }, data: ['remote_delete' => $remoteStatus]);
    }
}
