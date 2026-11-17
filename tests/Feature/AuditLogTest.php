<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Segment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);
    }

    /**
     * Teste: criação de registro gera evento "created".
     */
    public function test_created_event_is_logged(): void
    {
        $response = $this->postJson('/api/v1/segments', [
            'name' => 'Segmento Auditado',
            'slug' => 'segmento-auditado',
        ]);

        $response->assertStatus(201);

        $log = AuditLog::query()
            ->where('auditable_type', Segment::class)
            ->where('auditable_id', $response->json('data.id'))
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($this->user->id, $log->user_id);
        $this->assertNotEmpty($log->new_values);
    }

    /**
     * Teste: atualização de status gera "updated" e "status_changed".
     */
    public function test_status_change_events_are_logged(): void
    {
        $company = $this->createCompany(['status' => 'prospecting']);

        $response = $this->putJson("/api/v1/companies/{$company->id}", [
            'status' => 'customer',
        ]);

        $response->assertStatus(200);

        $logs = AuditLog::query()
            ->where('auditable_type', Company::class)
            ->where('auditable_id', $company->id)
            ->pluck('event')
            ->all();

        $this->assertContains('updated', $logs);
        $this->assertContains('status_changed', $logs);

        $statusChanged = AuditLog::query()
            ->where('auditable_type', Company::class)
            ->where('auditable_id', $company->id)
            ->where('event', 'status_changed')
            ->firstOrFail();

        $this->assertSame('prospecting', $statusChanged->old_values['status']);
        $this->assertSame('customer', $statusChanged->new_values['status']);
    }

    /**
     * Teste: exclusão gera evento "deleted".
     */
    public function test_deleted_event_is_logged(): void
    {
        $segment = Segment::create([
            'name' => 'Segmento Excluído',
            'slug' => 'segmento-excluido',
        ]);

        $response = $this->deleteJson("/api/v1/segments/{$segment->id}");

        $response->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Segment::class,
            'auditable_id' => $segment->id,
            'event' => 'deleted',
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * Teste: inclusão de nota gera evento "note_added".
     */
    public function test_note_added_event_is_logged(): void
    {
        $contact = Contact::create([
            'name' => 'Contato Auditado',
            'company_id' => $this->createCompany()->id,
        ]);

        $response = $this->putJson("/api/v1/contacts/{$contact->id}", [
            'notes' => 'Nota adicionada',
        ]);

        $response->assertStatus(200);

        $log = AuditLog::query()
            ->where('auditable_type', Contact::class)
            ->where('auditable_id', $contact->id)
            ->where('event', 'note_added')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Nota adicionada', $log->new_values['notes']);
    }

    /**
     * Teste: credenciais não são gravadas no log de auditoria.
     */
    public function test_sensitive_fields_are_not_logged(): void
    {
        $response = $this->putJson("/api/v1/users/{$this->user->id}", [
            'password' => 'novasenha123',
        ]);

        $response->assertStatus(200);

        $log = AuditLog::query()
            ->where('auditable_type', User::class)
            ->where('auditable_id', $this->user->id)
            ->where('event', 'updated')
            ->firstOrFail();

        $this->assertArrayNotHasKey('password', $log->new_values);
        $this->assertArrayNotHasKey('remember_token', $log->new_values);
    }

    /**
     * Teste: o próprio log de auditoria não gera novos logs (sem recursão).
     */
    public function test_audit_log_is_not_audited(): void
    {
        Segment::create([
            'name' => 'Segmento Simples',
            'slug' => 'segmento-simples',
        ]);

        $this->assertDatabaseMissing('audit_logs', [
            'auditable_type' => AuditLog::class,
        ]);
    }
}
