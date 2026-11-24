<?php

namespace App\Http\Controllers\Web;

use App\Actions\SendWhatsAppMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\SendWhatsAppMessageRequest;
use App\Models\Contact;
use App\Models\WhatsAppMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class WhatsAppInboxController extends Controller
{
    public function index(Request $request): View
    {
        return $this->renderInbox($request);
    }

    public function show(Request $request, Contact $contact): View
    {
        $selected = $this->scopedContacts()->findOrFail($contact->getKey());
        Gate::authorize('view', $selected);

        return $this->renderInbox($request, $selected);
    }

    public function store(
        SendWhatsAppMessageRequest $request,
        Contact $contact,
        SendWhatsAppMessage $action
    ): RedirectResponse {
        $this->scopedContacts()->findOrFail($contact->getKey());
        Gate::authorize('update', $contact);

        $action->handle($contact, $request->validated()['body'], $request->user());

        return redirect()->route('web.whatsapp.inbox.contact', $contact)
            ->with('status', 'Mensagem enfileirada para envio.');
    }

    private function renderInbox(Request $request, ?Contact $selected = null): View
    {
        $search = trim((string) $request->query('q', ''));

        $contacts = $this->scopedContacts()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('contacts.name', 'like', "%{$search}%")
                        ->orWhere('contacts.phone', 'like', "%{$search}%")
                        ->orWhere('contacts.whatsapp', 'like', "%{$search}%")
                        ->orWhere('companies.trade_name', 'like', "%{$search}%");
                });
            })
            ->orderByRaw('COALESCE(latest_message.latest_sent_at, latest_message.latest_created_at) DESC')
            ->orderByDesc('contacts.updated_at')
            ->orderByDesc('contacts.id')
            ->paginate(15)
            ->withQueryString();

        $messages = $selected?->whatsappMessages()
            ->with('sender:id,name')
            ->latest('created_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        if ($messages !== null) {
            // Paginate newest first, then display oldest first so the latest
            // message sits at the bottom of the conversation.
            $messages->setCollection($messages->getCollection()->reverse()->values());
        }

        return view('whatsapp.inbox', [
            'contacts' => $contacts,
            'selected' => $selected,
            'messages' => $messages,
            'search' => $search,
            'storeRoute' => $selected
                ? route('web.whatsapp.inbox.store', $selected)
                : null,
        ]);
    }

    private function scopedContacts(): Builder
    {
        $latestMessages = WhatsAppMessage::query()
            ->select([
                'wm.contact_id',
                'wm.body as latest_body',
                'wm.direction as latest_direction',
                'wm.status as latest_status',
                'wm.sent_at as latest_sent_at',
                'wm.created_at as latest_created_at',
            ])
            ->from('whatsapp_messages as wm')
            ->whereRaw('wm.id = (select max(wm2.id) from whatsapp_messages as wm2 where wm2.contact_id = wm.contact_id)');

        $query = Contact::query()
            ->select('contacts.*')
            ->leftJoin('companies', 'companies.id', '=', 'contacts.company_id')
            ->leftJoinSub($latestMessages, 'latest_message', function ($join): void {
                $join->on('latest_message.contact_id', '=', 'contacts.id');
            })
            ->whereNotNull('contacts.whatsapp')
            ->with(['company:id,trade_name', 'assignedUser:id,name']);

        $user = auth()->user();

        if (! in_array($user->role, ['admin', 'manager'], true)) {
            $query->where('contacts.assigned_user_id', $user->id);
        }

        return $query;
    }
}
