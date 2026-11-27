<?php

namespace App\Actions;

use App\Contracts\WhatsAppGateway;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Support\WhatsAppMessageTarget;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class DeleteWhatsAppMessage
{
    public const FAILED_MESSAGE = 'Não foi possível apagar a mensagem no WhatsApp. A mensagem foi mantida no CRM.';

    public function __construct(private readonly WhatsAppGateway $gateway) {}

    public function handle(WhatsAppMessage $message, User $deleter): string
    {
        $result = $this->deleteOnWhatsApp($message);
        $remoteStatus = $result['status'];

        $metadata = $message->metadata ?? [];
        $metadata['remote_delete'] = $remoteStatus;

        if (array_key_exists('provider_status', $result)) {
            $metadata['remote_delete_status'] = $result['provider_status'];
        }

        if (array_key_exists('reason', $result)) {
            $metadata['remote_delete_reason'] = $result['reason'];
        }

        DB::transaction(function () use ($message, $deleter, $remoteStatus, $metadata): void {
            if ($remoteStatus === 'failed') {
                // The WhatsApp message is still there, so the CRM must keep
                // showing it. Only the failed attempt is recorded for audit.
                $message->forceFill(['metadata' => $metadata])->save();

                return;
            }

            $message->forceFill([
                'deleted_by' => $deleter->id,
                'metadata' => $metadata,
            ])->save();
            $message->delete();
        });

        return $remoteStatus;
    }

    /**
     * @return array{status: string, provider_status?: int, reason?: string}
     */
    private function deleteOnWhatsApp(WhatsAppMessage $message): array
    {
        $providerMessageId = $message->provider_message_id;

        if (! is_string($providerMessageId) || $providerMessageId === '') {
            // Never sent through WhatsApp: hiding it locally cannot desync a
            // conversation that does not exist on the phone.
            return ['status' => 'skipped'];
        }

        if ($message->direction !== WhatsAppMessage::DIRECTION_OUTBOUND) {
            // WhatsApp only allows revoking messages sent by this session.
            return ['status' => 'failed', 'reason' => 'not_from_me'];
        }

        $remoteJid = WhatsAppMessageTarget::remoteJid($message);

        if ($remoteJid === null) {
            return ['status' => 'failed', 'reason' => 'missing_target'];
        }

        try {
            $this->gateway->deleteForEveryone($providerMessageId, $remoteJid, true);
        } catch (Throwable $exception) {
            $context = [
                'whatsapp_message_id' => $message->id,
                'exception' => $exception::class,
            ];
            $result = ['status' => 'failed'];

            if ($exception instanceof RequestException) {
                $response = $exception->response;

                $context += [
                    'status' => $response->status(),
                    'url' => (string) $response->effectiveUri(),
                    'body' => Str::limit($response->body(), 500),
                ];

                $result['provider_status'] = $response->status();
            } else {
                $context['error'] = $exception->getMessage();
            }

            Log::warning('Falha ao apagar mensagem no WhatsApp.', $context);

            return $result;
        }

        return ['status' => 'deleted'];
    }
}
