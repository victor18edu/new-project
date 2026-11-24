<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\WhatsApp\EvolutionApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class WhatsAppConnectionController extends Controller
{
    public function __construct(private readonly EvolutionApiClient $client) {}

    public function index(): View
    {
        Gate::authorize('manage-whatsapp-connection');

        return view('whatsapp.connection', $this->connectionData());
    }

    public function store(): RedirectResponse
    {
        Gate::authorize('manage-whatsapp-connection');

        try {
            if ($this->client->instanceState() === null) {
                $this->client->createInstance();
            }

            $this->client->configureWebhook();
        } catch (Throwable $exception) {
            Log::error('Falha ao configurar a conexão do WhatsApp.', [
                'exception' => $exception::class,
            ]);

            return redirect()->route('web.whatsapp.connection')
                ->with('error', 'Não foi possível configurar a conexão. Verifique os logs e tente novamente.');
        }

        return redirect()->route('web.whatsapp.connection')
            ->with('status', 'Instância e webhook configurados. Escaneie o QR Code se necessário.');
    }

    public function qr(): JsonResponse
    {
        Gate::authorize('manage-whatsapp-connection');

        return response()->json($this->connectionData());
    }

    /**
     * @return array{state: string, qr: string|null, instance: mixed}
     */
    private function connectionData(): array
    {
        try {
            $state = $this->client->instanceState();

            return [
                'state' => $state ?? 'not_found',
                'qr' => $state === 'open' ? null : $this->client->qrCode(),
                'instance' => config('services.evolution.instance'),
            ];
        } catch (Throwable $exception) {
            Log::error('Falha ao consultar o status do WhatsApp.', [
                'exception' => $exception::class,
            ]);

            return [
                'state' => 'error',
                'qr' => null,
                'instance' => config('services.evolution.instance'),
            ];
        }
    }
}
