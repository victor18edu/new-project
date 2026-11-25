<?php

namespace App\Http\Controllers\Web;

use App\Actions\DeleteWhatsAppMessage;
use App\Actions\EditWhatsAppMessage;
use App\Actions\RetryWhatsAppMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\UpdateWhatsAppMessageRequest;
use App\Models\Contact;
use App\Models\WhatsAppMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WhatsAppMessageController extends Controller
{
    public function update(
        UpdateWhatsAppMessageRequest $request,
        Contact $contact,
        WhatsAppMessage $message,
        EditWhatsAppMessage $action
    ): RedirectResponse {
        abort_unless($message->contact_id === $contact->id, 404);

        Gate::authorize('update', $message);

        $action->handle($message, $request->validated()['body'], $request->user());

        return back()->with('status', 'Mensagem editada no histórico.');
    }

    public function retry(
        Contact $contact,
        WhatsAppMessage $message,
        RetryWhatsAppMessage $action
    ): RedirectResponse {
        abort_unless($message->contact_id === $contact->id, 404);

        Gate::authorize('retry', $message);

        $action->handle($message);

        return back()->with('status', 'Mensagem reenviada para a fila de envio.');
    }

    public function destroy(
        Request $request,
        Contact $contact,
        WhatsAppMessage $message,
        DeleteWhatsAppMessage $action
    ): RedirectResponse {
        abort_unless($message->contact_id === $contact->id, 404);

        Gate::authorize('delete', $message);

        $remoteStatus = $action->handle($message, $request->user());

        return back()->with('status', match ($remoteStatus) {
            'deleted' => 'Mensagem apagada da conversa e do WhatsApp.',
            'failed' => 'Mensagem apagada da conversa, mas não foi possível apagá-la no WhatsApp.',
            default => 'Mensagem apagada da conversa.',
        });
    }
}
