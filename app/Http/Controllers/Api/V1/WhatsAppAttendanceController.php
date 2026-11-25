<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CloseWhatsAppAttendance;
use App\Actions\StartWhatsAppAttendance;
use App\Models\Contact;
use App\Models\WhatsAppAttendance;
use App\Support\OperatorRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WhatsAppAttendanceController extends BaseApiController
{
    public function index(Request $request)
    {
        Gate::authorize('viewHistory', Contact::class);

        $search = trim((string) $request->query('q', ''));
        $operatorId = (string) $request->query('operator_id', '');
        $status = (string) $request->query('status', '');
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');

        $query = WhatsAppAttendance::query()
            ->with(['contact:id,name,whatsapp', 'operator:id,name', 'assignedBy:id,name'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->whereHas('contact', function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('whatsapp', 'like', "%{$search}%");
                });
            })
            ->when($operatorId !== '', fn (Builder $query) => $query->where('operator_id', $operatorId))
            ->when(
                in_array($status, array_keys(WhatsAppAttendance::STATUS_LABELS), true),
                fn (Builder $query) => $query->where('status', $status)
            )
            ->when($from !== '', fn (Builder $query) => $query->whereDate('started_at', '>=', $from))
            ->when($to !== '', fn (Builder $query) => $query->whereDate('started_at', '<=', $to));

        $user = $request->user();

        if (! OperatorRole::canViewAllConversations($user)) {
            $query->where('operator_id', $user->id);
        }

        $attendances = $query->orderByDesc('started_at')
            ->orderByDesc('id')
            ->paginate(15);

        return $this->paginatedResponse($attendances);
    }

    public function show(WhatsAppAttendance $attendance)
    {
        Gate::authorize('view', $attendance);

        return $this->successResponse(
            message: 'Atendimento encontrado.',
            data: $attendance->load(['contact', 'operator:id,name', 'assignedBy:id,name'])
        );
    }

    public function store(
        Request $request,
        Contact $contact,
        StartWhatsAppAttendance $action
    ) {
        Gate::authorize('assign', $contact);

        $attendance = $action->handle($contact, $request->user(), $request->user());

        return $this->successResponse(
            message: 'Atendimento assumido com sucesso.',
            data: $attendance,
            status: 201
        );
    }

    public function close(
        Request $request,
        WhatsAppAttendance $attendance,
        CloseWhatsAppAttendance $action
    ) {
        Gate::authorize('close', $attendance);

        $validated = $request->validate([
            'outcome_notes' => 'nullable|string|max:1000',
        ]);

        $attendance = $action->handle($attendance, $validated['outcome_notes'] ?? null);

        return $this->successResponse(
            message: 'Atendimento encerrado com sucesso.',
            data: $attendance
        );
    }
}
