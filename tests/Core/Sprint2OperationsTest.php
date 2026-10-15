<?php

namespace Tests\Core;

use App\Core\Domain\Tenant\TenantContext;
use App\Core\Application\Module\ModuleGate;
use Illuminate\Support\Facades\DB;
use Modules\Agenda\Domain\Appointment;
use Modules\Agenda\Domain\Resource;
use Modules\Agenda\Domain\Availability;
use Modules\Atendimento\Domain\ServiceCase;
use Modules\Atendimento\Domain\ServiceNote;
use Modules\Atendimento\Domain\CaseHistory;
use Modules\Cadastro\Domain\Person;
use Modules\Cadastro\Contracts\PeopleDirectory;

class Sprint2OperationsTest extends TestCase
{
    private $tenantA;
    private $tenantB;
    private $operator;
    private $member;
    private $personA;
    private $personB;
    private $resourceA;
    private $resourceB;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('app.key', str_repeat('t', 32)); // Ephemeral test application only.
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Register after Core's test database is created. No production connection.
        foreach ([\Modules\Cadastro\Providers\CadastroServiceProvider::class,
            \Modules\Agenda\Providers\AgendaServiceProvider::class,
            \Modules\Atendimento\Providers\AtendimentoServiceProvider::class] as $provider) {
            $this->app->register($provider);
        }
        $this->app['router']->getRoutes()->refreshNameLookups();
        foreach (['Cadastro', 'Agenda', 'Atendimento'] as $module) {
            foreach (glob(dirname(__DIR__, 2) . '/Modules/' . $module . '/database/migrations/*.php') as $path) {
                (require $path)->up();
            }
        }
        $this->tenantA = $this->createTenant('Empresa A');
        $this->tenantB = $this->createTenant('Empresa B');
        $this->operator = $this->createUser()->refresh();
        $this->member = $this->attachUserToTenant($this->operator, $this->tenantA);
        foreach (['cadastro', 'agenda', 'atendimento'] as $key) {
            $this->enableModuleForTenant($this->tenantA, $this->createModule($key));
        }
        $role = $this->createRole('operator', $this->tenantA);
        $this->assignRoleToTenantUser($this->member, $role);
        foreach (['agenda.view','agenda.create','agenda.update','agenda.delete','agenda.resources.manage',
            'atendimento.view','atendimento.create','atendimento.update','atendimento.close','atendimento.notes.manage'] as $key) {
            $this->giveRolePermission($role, $this->createPermission($key));
        }
        TenantContext::set($this->tenantA->ulid);
        $this->personA = Person::create(['full_name' => 'Pessoa A']);
        $this->resourceA = Resource::create(['name' => 'Sala A']);
        TenantContext::set($this->tenantB->ulid);
        $this->personB = Person::create(['full_name' => 'Pessoa B']);
        $this->resourceB = Resource::create(['name' => 'Sala B']);
        TenantContext::clear();
        $this->actingAs($this->operator)->withSession(['current_tenant_id' => $this->tenantA->ulid]);
    }

    private function booking(array $overrides = []): array
    {
        return array_merge(['title' => 'Consulta', 'starts_at' => '2026-10-19 09:00:00',
            'ends_at' => '2026-10-19 10:00:00', 'person_id' => $this->personA->ulid,
            'resource_id' => $this->resourceA->ulid], $overrides);
    }

    public function test_booking_reschedule_conflicts_and_cancellation(): void
    {
        $response = $this->postJson('/api/agenda/compromissos', $this->booking(['tenant_id' => $this->tenantB->ulid]))->assertCreated();
        $id = $response->json('id');
        $this->assertDatabaseHas('agenda_appointments', ['ulid' => $id, 'tenant_id' => $this->tenantA->ulid]);
        $this->postJson('/api/agenda/compromissos', $this->booking())->assertUnprocessable();
        $this->putJson('/api/agenda/compromissos/' . $id, $this->booking([
            'starts_at' => '2026-10-19 10:00:00', 'ends_at' => '2026-10-19 11:00:00']))->assertOk();
        $this->postJson('/api/agenda/compromissos', $this->booking())->assertCreated();
        $this->postJson('/api/agenda/compromissos/' . $id . '/cancelar')->assertOk();
        $this->putJson('/api/agenda/compromissos/' . $id, $this->booking())->assertUnprocessable();
    }

    public function test_foreign_people_resources_and_invalid_dates_are_rejected(): void
    {
        $this->postJson('/api/agenda/compromissos', $this->booking(['person_id' => $this->personB->ulid]))->assertUnprocessable();
        $this->postJson('/api/agenda/compromissos', $this->booking(['resource_id' => $this->resourceB->ulid]))->assertUnprocessable();
        $this->postJson('/api/agenda/compromissos', $this->booking(['ends_at' => '2026-10-19 08:00:00']))->assertUnprocessable();
    }

    public function test_availability_and_resource_operations(): void
    {
        $data = ['resource_id' => $this->resourceA->ulid, 'weekday' => 1, 'starts_at' => '10:00', 'ends_at' => '17:00'];
        $id = $this->postJson('/api/agenda/disponibilidade', $data)->assertCreated()->json('ulid');
        $this->postJson('/api/agenda/disponibilidade', $data)->assertUnprocessable();
        $this->postJson('/api/agenda/compromissos', $this->booking())->assertUnprocessable();
        $this->putJson('/api/agenda/disponibilidade/' . $id, array_merge($data, ['starts_at' => '08:00']))->assertOk();
        $this->postJson('/api/agenda/compromissos', $this->booking())->assertCreated();
        $this->deleteJson('/api/agenda/disponibilidade/' . $id)->assertOk();
        $this->putJson('/api/agenda/recursos/' . $this->resourceA->ulid,
            ['name' => 'Sala A', 'type' => 'room', 'is_active' => false])->assertOk();
        $this->postJson('/api/agenda/compromissos', $this->booking())->assertUnprocessable();
        $this->postJson('/api/agenda/recursos', ['name' => 'Sala nova', 'type' => 'room'])->assertCreated();
    }

    public function test_cases_notes_responsibility_history_and_idempotent_close(): void
    {
        $id = $this->postJson('/api/atendimento/casos', ['subject' => 'Pedido', 'person_id' => $this->personA->ulid,
            'responsible_user_id' => $this->operator->ulid, 'tenant_id' => $this->tenantB->ulid,
            'status' => 'closed'])->assertCreated()->json('ulid');
        $this->assertDatabaseHas('atendimento_cases', ['ulid' => $id, 'status' => 'open', 'tenant_id' => $this->tenantA->ulid]);
        $this->putJson('/api/atendimento/casos/' . $id, ['subject' => 'Pedido atualizado',
            'status' => 'in_progress', 'responsible_user_id' => $this->operator->ulid])->assertOk();
        $this->postJson('/api/atendimento/casos/' . $id . '/notas', ['body' => 'Contato realizado',
            'author_user_id' => $this->personB->ulid])->assertCreated();
        $this->assertDatabaseHas('atendimento_notes', ['case_id' => $id, 'author_user_id' => $this->operator->ulid]);
        $this->getJson('/api/atendimento/casos/' . $id)->assertOk()->assertJsonCount(1, 'notes')->assertJsonCount(3, 'history');
        $this->postJson('/api/atendimento/casos/' . $id . '/encerrar')->assertOk();
        $this->postJson('/api/atendimento/casos/' . $id . '/encerrar')->assertOk();
        $this->assertDatabaseCount('atendimento_history', 4);
        $this->postJson('/api/atendimento/casos/' . $id . '/notas', ['body' => 'Alterar encerrado'])->assertUnprocessable();
        $this->putJson('/api/atendimento/casos/' . $id, ['subject' => 'Reabrir', 'status' => 'open'])->assertUnprocessable();
    }

    public function test_foreign_cases_notes_appointments_and_availability_are_inaccessible(): void
    {
        TenantContext::set($this->tenantB->ulid);
        $case = ServiceCase::create(['subject' => 'Segredo B', 'person_id' => $this->personB->ulid, 'opened_at' => now()]);
        $booking = Appointment::create($this->booking(['resource_id' => $this->resourceB->ulid]));
        $window = Availability::create(['resource_id' => $this->resourceB->ulid, 'weekday' => 1, 'starts_at' => '09:00', 'ends_at' => '12:00']);
        TenantContext::clear();
        $this->getJson('/api/atendimento/casos/' . $case->ulid)->assertNotFound();
        $this->postJson('/api/atendimento/casos/' . $case->ulid . '/notas', ['body' => 'Invasão'])->assertNotFound();
        $this->postJson('/api/atendimento/casos/' . $case->ulid . '/encerrar')->assertNotFound();
        $this->putJson('/api/atendimento/casos/' . $case->ulid, ['subject' => 'Invasão', 'status' => 'open'])->assertNotFound();
        $this->putJson('/api/agenda/compromissos/' . $booking->ulid, $this->booking())->assertNotFound();
        $this->postJson('/api/agenda/compromissos/' . $booking->ulid . '/cancelar')->assertNotFound();
        $this->deleteJson('/api/agenda/disponibilidade/' . $window->ulid)->assertNotFound();
        $this->putJson('/api/agenda/recursos/' . $this->resourceB->ulid, ['name' => 'Invasão', 'type' => 'room'])->assertNotFound();
        $this->getJson('/api/atendimento')->assertOk()->assertJsonMissing(['subject' => 'Segredo B']);
    }

    public function test_responsible_must_be_active_member_of_current_tenant(): void
    {
        $other = $this->createUser();
        $this->attachUserToTenant($other, $this->tenantB);
        TenantContext::clear();
        $this->postJson('/api/atendimento/casos', ['subject' => 'Pedido', 'person_id' => $this->personA->ulid,
            'responsible_user_id' => $other->ulid])->assertUnprocessable();
        $this->postJson('/api/atendimento/casos', ['subject' => 'Pedido', 'person_id' => $this->personB->ulid])->assertUnprocessable();
    }

    public function test_web_views_render_people_and_case_history_safely(): void
    {
        $this->postJson('/api/agenda/compromissos', $this->booking())->assertCreated();
        $this->postJson('/api/agenda/disponibilidade', ['resource_id' => $this->resourceA->ulid,
            'weekday' => 1, 'starts_at' => '08:00', 'ends_at' => '18:00'])->assertCreated();
        $agenda = $this->get('/agenda')->assertOk()->assertSee('Pessoa A')->assertDontSee('Pessoa B');
        $cases = $this->get('/atendimento')->assertOk()->assertSee('Pessoa A')->assertDontSee('Pessoa B');
        $id = $this->postJson('/api/atendimento/casos', ['subject' => '<script>alert(1)</script>',
            'person_id' => $this->personA->ulid])->assertCreated()->json('ulid');
        $this->postJson('/api/atendimento/casos/' . $id . '/notas', ['body' => 'Contato de teste realizado.'])->assertCreated();
        $detail = $this->get('/atendimento/casos/' . $id)->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>', false);
        if ($directory = getenv('SPRINT2_UI_OUTPUT')) {
            if (!is_dir($directory)) { mkdir($directory, 0775, true); }
            foreach (['agenda' => $agenda, 'atendimentos' => $cases, 'atendimento' => $detail] as $name => $response) {
                file_put_contents($directory . '/' . $name . '.html', $response->getContent());
            }
        }
    }

    public function test_disabled_module_is_blocked_on_server(): void
    {
        TenantContext::set($this->tenantA->ulid);
        $this->app->make(ModuleGate::class)->disable('agenda');
        TenantContext::clear();
        $this->postJson('/api/agenda/compromissos', $this->booking())->assertForbidden();
        $this->get('/agenda')->assertForbidden();
    }

    public function test_missing_permissions_block_every_mutation(): void
    {
        DB::table('tenant_user_roles')->delete();
        foreach ([['POST','/api/agenda/compromissos'],['PUT','/api/agenda/compromissos/x'],
            ['POST','/api/agenda/compromissos/x/cancelar'],['POST','/api/agenda/recursos'],
            ['PUT','/api/agenda/recursos/x'],['POST','/api/agenda/disponibilidade'],
            ['PUT','/api/agenda/disponibilidade/x'],['DELETE','/api/agenda/disponibilidade/x'],
            ['POST','/api/atendimento/casos'],['PUT','/api/atendimento/casos/x'],
            ['POST','/api/atendimento/casos/x/notas'],['POST','/api/atendimento/casos/x/encerrar']] as [$verb,$url]) {
            $this->json($verb, $url)->assertForbidden();
        }
    }

    public function test_inactive_membership_is_blocked(): void
    {
        DB::table('tenant_users')->where('ulid', $this->member->ulid)->update(['status' => 'inactive']);
        $this->get('/agenda')->assertForbidden();
        $this->postJson('/api/atendimento/casos', ['person_id' => $this->personA->ulid, 'subject' => 'Pedido'])->assertForbidden();
    }

    public function test_directory_contract_and_architecture(): void
    {
        TenantContext::set($this->tenantA->ulid);
        $directory = $this->app->make(PeopleDirectory::class);
        $this->assertTrue($directory->contains($this->personA->ulid));
        $this->assertFalse($directory->contains($this->personB->ulid));
        foreach (['Agenda', 'Atendimento'] as $module) {
            foreach (glob(dirname(__DIR__, 2) . '/Modules/' . $module . '/Http/Controllers/*.php') as $path) {
                $this->assertStringNotContainsString('Modules\\Cadastro\\Domain', file_get_contents($path));
                $this->assertStringNotContainsString('cadastro_people', file_get_contents($path));
            }
        }
    }
}
