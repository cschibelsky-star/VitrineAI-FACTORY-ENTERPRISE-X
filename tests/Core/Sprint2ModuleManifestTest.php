<?php

namespace Tests\Core;

use App\Core\Application\Module\ModuleRegistry;

class Sprint2ModuleManifestTest extends TestCase
{
    /** @test */
    public function agenda_and_atendimento_use_the_canonical_manifest_contract(): void
    {
        $registry = app(ModuleRegistry::class);

        foreach (['Agenda', 'Atendimento'] as $module) {
            $registry->register(dirname(__DIR__, 2) . "/Modules/{$module}/module.json");
        }

        $registry->boot();

        $this->assertTrue($registry->has('agenda'));
        $this->assertTrue($registry->has('atendimento'));
        $this->assertSame('Modules\\Agenda\\Providers\\AgendaServiceProvider', $registry->get('agenda')['service_provider']);
        $this->assertSame('Modules\\Atendimento\\Providers\\AtendimentoServiceProvider', $registry->get('atendimento')['service_provider']);
        $this->assertContains('cadastro', $registry->get('agenda')['dependencies']['requires']);
        $this->assertContains('cadastro', $registry->get('atendimento')['dependencies']['requires']);
    }
}
