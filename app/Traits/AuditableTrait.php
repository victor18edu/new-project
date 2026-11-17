<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Trait AuditableTrait
 *
 * Registra automaticamente os eventos exigidos pela especificação
 * (created, updated, deleted, status_changed e note_added) na tabela audit_logs.
 */
trait AuditableTrait
{
    /**
     * Register the Eloquent listeners that feed the audit trail.
     */
    protected static function bootAuditableTrait(): void
    {
        static::created(function (Model $model): void {
            if (! static::shouldAudit($model)) {
                return;
            }

            static::writeAuditLog($model, 'created', [], $model->getAttributes());
        });

        static::updated(function (Model $model): void {
            if (! static::shouldAudit($model)) {
                return;
            }

            $previous = $model->getPrevious();
            $changes = $model->getChanges();

            static::writeAuditLog($model, 'updated', $previous, $changes);

            if (array_key_exists('status', $changes)) {
                static::writeAuditLog(
                    $model,
                    'status_changed',
                    ['status' => $previous['status'] ?? null],
                    ['status' => $changes['status']]
                );
            }

            foreach (['notes', 'outcome_notes', 'pain_points_notes', 'lost_reason_notes'] as $noteField) {
                if (array_key_exists($noteField, $changes) && filled($changes[$noteField])) {
                    static::writeAuditLog(
                        $model,
                        'note_added',
                        [$noteField => $previous[$noteField] ?? null],
                        [$noteField => $changes[$noteField]]
                    );
                }
            }
        });

        static::deleted(function (Model $model): void {
            if (! static::shouldAudit($model)) {
                return;
            }

            static::writeAuditLog($model, 'deleted', $model->getAttributes(), []);
        });
    }

    /**
     * Prevent the audit log itself from generating new audit log entries.
     */
    protected static function shouldAudit(Model $model): bool
    {
        return ! $model instanceof AuditLog;
    }

    /**
     * Persist one audit log entry for the given event.
     *
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    protected static function writeAuditLog(Model $model, string $event, array $oldValues, array $newValues): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'event' => $event,
            'old_values' => $oldValues === [] ? null : static::filterAuditValues($oldValues),
            'new_values' => $newValues === [] ? null : static::filterAuditValues($newValues),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Remove sensitive attributes before storing them as JSON.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected static function filterAuditValues(array $values): array
    {
        unset($values['password'], $values['remember_token']);

        return $values;
    }
}
