<?php

namespace App\Http\Controllers\Web;

use App\Actions\CloseWhatsAppAttendance;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\CustomerCategory;
use App\Models\User;
use App\Models\WhatsAppAttendance;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppMessageRevision;
use App\Support\OperatorRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class WhatsAppHistoryController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewHistory', Contact::class);

        $search = trim((string) $request->query('q', ''));
        $operatorId = (string) $request->query('operator_id', '');
        $status = (string) $request->query('status', '');
        $categoryId = (string) $request->query('customer_category_id', '');
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');

        $query = WhatsAppAttendance::query()
            ->with(['contact.company:id,trade_name', 'operator:id,name', 'assignedBy:id,name'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->whereHas('contact', function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('whatsapp', 'like', "%{$search}%");
                });
            })
            ->when($operatorId !== '', fn (Builder $query) => $query->where('operator_id', $operatorId))
            ->when(in_array($status, array_keys(WhatsAppAttendance::STATUS_LABELS), true), fn (Builder $query) => $query->where('status', $status))
            ->when($categoryId !== '', function (Builder $query) use ($categoryId): void {
                $query->whereHas('contact', fn (Builder $query) => $query->where('customer_category_id', $categoryId));
            })
            ->when($from !== '', fn (Builder $query) => $query->whereDate('started_at', '>=', $from))
            ->when($to !== '', fn (Builder $query) => $query->whereDate('started_at', '<=', $to));

        if (! OperatorRole::canViewAllConversations($request->user())) {
            $query->where('operator_id', $request->user()->id);
        }

        $attendances = $query->orderByDesc('started_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $revisions = WhatsAppMessageRevision::query()
            ->with(['message.contact:id,name', 'editor:id,name']);

        $deletedMessages = WhatsAppMessage::onlyTrashed()
            ->with(['contact:id,name', 'deleter:id,name']);

        if (! OperatorRole::canViewAllConversations($request->user())) {
            $revisions->whereHas('message', fn (Builder $query) => $query->where('user_id', $request->user()->id));
            $deletedMessages->where('user_id', $request->user()->id);
        }

        return view('whatsapp.history', [
            'attendances' => $attendances,
            'revisions' => $revisions->latest()->limit(20)->get(),
            'deletedMessages' => $deletedMessages->latest('deleted_at')->limit(20)->get(),
            'statuses' => WhatsAppAttendance::STATUS_LABELS,
            'operators' => User::query()
                ->whereIn('role', array_keys(OperatorRole::labels()))
                ->orderBy('name')
                ->pluck('name', 'id'),
            'categories' => CustomerCategory::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name', 'id'),
            'filters' => [
                'q' => $search,
                'operator_id' => $operatorId,
                'status' => $status,
                'customer_category_id' => $categoryId,
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    public function close(
        Request $request,
        WhatsAppAttendance $attendance,
        CloseWhatsAppAttendance $action
    ): RedirectResponse {
        Gate::authorize('close', $attendance);

        $validated = $request->validate([
            'outcome_notes' => 'nullable|string|max:1000',
        ]);

        $action->handle($attendance, $validated['outcome_notes'] ?? null);

        return back()->with('status', 'Atendimento encerrado com sucesso.');
    }
}
