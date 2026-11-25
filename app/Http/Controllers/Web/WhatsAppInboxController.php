<?php

namespace App\Http\Controllers\Web;

use App\Actions\SendWhatsAppMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\SendWhatsAppMessageRequest;
use App\Models\Contact;
use App\Models\CustomerCategory;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Support\OperatorRole;
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
        Gate::authorize('viewInbox', Contact::class);

        $selected = $this->scopedContacts()->findOrFail($contact->getKey());
        Gate::authorize('viewConversation', $selected);

        return $this->renderInbox($request, $selected);
    }

    public function store(
        SendWhatsAppMessageRequest $request,
        Contact $contact,
        SendWhatsAppMessage $action
    ): RedirectResponse {
        Gate::authorize('sendMessage', $contact);

        $this->scopedContacts()->findOrFail($contact->getKey());
        $action->handle($contact, $request->validated()['body'], $request->user());

        return redirect()->route('web.whatsapp.inbox.contact', $contact)
            ->with('status', 'Mensagem enfileirada para envio.');
    }

    public function assume(Contact $contact, StartWhatsAppAttendance $action): RedirectResponse
    {
        Gate::authorize('viewInbox', Contact::class);

        $selected = $this->scopedContacts()->findOrFail($contact->getKey());
        Gate::authorize('assign', $selected);

        $action->handle($selected, auth()->user());

        return redirect()->route('web.whatsapp.inbox.contact', $selected)
            ->with('status', 'Atendimento assumido com sucesso.');
    }

    private function renderInbox(Request $request, ?Contact $selected = null): View
    {
        Gate::authorize('viewInbox', Contact::class);

        $search = trim((string) $request->query('q', ''));
        $operatorId = (string) $request->query('operator_id', '');
        $categoryId = (string) $request->query('customer_category_id', '');

        $contacts = $this->scopedContacts($operatorId, $categoryId)
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
            ->with(['sender:id,name', 'editor:id,name'])
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
            'operatorId' => $operatorId,
            'categoryId' => $categoryId,
            'operators' => User::query()
                ->whereIn('role', array_keys(OperatorRole::labels()))
                ->orderBy('name')
                ->pluck('name', 'id'),
            'categories' => CustomerCategory::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name', 'id'),
            'currentAttendance' => $selected?->whatsappAttendances()
                ->with('operator:id,name')
                ->open()
                ->first(),
            'storeRoute' => $selected
                ? route('web.whatsapp.inbox.store', $selected)
                : null,
        ]);
    }

    private function scopedContacts(string $operatorId = '', string $categoryId = ''): Builder
    {
        // SoftDeletes excludes deleted messages automatically. The subquery is
        // deliberately unaliased so the global scope keeps working.
        $latestMessages = WhatsAppMessage::query()
            ->select([
                'whatsapp_messages.contact_id',
                'whatsapp_messages.body as latest_body',
                'whatsapp_messages.direction as latest_direction',
                'whatsapp_messages.status as latest_status',
                'whatsapp_messages.sent_at as latest_sent_at',
                'whatsapp_messages.created_at as latest_created_at',
            ])
            ->whereRaw('whatsapp_messages.id = (select max(wm2.id) from whatsapp_messages as wm2 '
                .'where wm2.contact_id = whatsapp_messages.contact_id and wm2.deleted_at is null)');

        $query = Contact::query()
            ->select('contacts.*')
            ->leftJoin('companies', 'companies.id', '=', 'contacts.company_id')
            ->leftJoinSub($latestMessages, 'latest_message', function ($join): void {
                $join->on('latest_message.contact_id', '=', 'contacts.id');
            })
            ->whereNotNull('contacts.whatsapp')
            ->with(['company:id,trade_name', 'customerCategory:id,name', 'assignedUser:id,name'])
            ->when($operatorId !== '', fn (Builder $query) => $query->where('contacts.assigned_user_id', $operatorId))
            ->when($categoryId !== '', fn (Builder $query) => $query->where('contacts.customer_category_id', $categoryId));

        $user = auth()->user();

        if (! OperatorRole::canViewAllConversations($user)) {
            $query->where('contacts.assigned_user_id', $user->id);
        }

        return $query;
    }
}
