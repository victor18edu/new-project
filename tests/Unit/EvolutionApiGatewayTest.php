<?php

namespace Tests\Unit;

use App\Contracts\WhatsAppGateway;
use App\Services\WhatsApp\EvolutionApiClient;
use App\Services\WhatsApp\EvolutionApiGateway;
use Mockery;
use Tests\TestCase;

class EvolutionApiGatewayTest extends TestCase
{
    public function test_gateway_delegates_send_text_to_the_client(): void
    {
        $client = Mockery::mock(EvolutionApiClient::class);
        $client->shouldReceive('sendText')
            ->once()
            ->with('+5511999999999', 'Olá')
            ->andReturn('BAE456');

        $gateway = new EvolutionApiGateway($client);

        $this->assertInstanceOf(WhatsAppGateway::class, $gateway);
        $this->assertSame('BAE456', $gateway->send('+5511999999999', 'Olá'));
    }

    public function test_gateway_delegates_update_message_to_the_client(): void
    {
        $client = Mockery::mock(EvolutionApiClient::class);
        $client->shouldReceive('updateMessage')
            ->once()
            ->with('BAE456', '5511999999999@s.whatsapp.net', '5511999999999', 'Texto novo');

        $gateway = new EvolutionApiGateway($client);

        $this->assertInstanceOf(WhatsAppGateway::class, $gateway);
        $gateway->updateMessage('BAE456', '5511999999999@s.whatsapp.net', '5511999999999', 'Texto novo');
    }

    public function test_gateway_delegates_delete_for_everyone_to_the_client(): void
    {
        $client = Mockery::mock(EvolutionApiClient::class);
        $client->shouldReceive('deleteForEveryone')
            ->once()
            ->with('BAE456', '5511999999999@s.whatsapp.net', true);

        $gateway = new EvolutionApiGateway($client);

        $this->assertInstanceOf(WhatsAppGateway::class, $gateway);
        $gateway->deleteForEveryone('BAE456', '5511999999999@s.whatsapp.net', true);
    }
}
