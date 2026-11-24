<?php

namespace App\Http\Controllers\Web;

use App\Actions\SendWhatsAppMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\SendWhatsAppMessageRequest;
use App\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class WhatsAppController extends Controller
{
    public function show(Contact $contact): View
    {
        Gate::authorize('view', $contact);

        $messages = $contact->whatsappMessages()
            ->with('sender:id,name')
            ->latest('created_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        // Paginate newest first, then display oldest first so the latest message
        // sits at the bottom of the conversation.
        $messages->setCollection($messages->getCollection()->reverse()->values());

        return view('whatsapp.thread', [
            'contact' => $contact,
            'messages' => $messages,
        ]);
    }

    public function store(
        SendWhatsAppMessageRequest $request,
        Contact $contact,
        SendWhatsAppMessage $action
    ): RedirectResponse {
        $action->handle($contact, $request->validated()['body'], $request->user());

        return redirect()->route('web.contacts.whatsapp.show', $contact)
            ->with('status', 'Mensagem enfileirada para envio.');
    }
}
