<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class WhatsAppRemoteEditFailed extends RuntimeException
{
    public const MESSAGE = 'Não foi possível editar a mensagem no WhatsApp. O texto original foi mantido no CRM.';

    public function __construct(private readonly int $messageId, ?Throwable $previous = null)
    {
        parent::__construct(self::MESSAGE, 0, $previous);
    }

    /** @return array<string, int> */
    public function context(): array
    {
        return ['whatsapp_message_id' => $this->messageId];
    }
}
