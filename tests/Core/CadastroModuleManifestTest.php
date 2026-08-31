<?php

namespace Tests\Core;

use App\Core\Application\Module\ModuleRegistry;

class CadastroModuleManifestTest extends TestCase
{
    /** @test */
    public function cadastro_module_uses_the_canonical_manifest_contract(): void
    {
        $manifest = dirname(__DIR__, 2) . '/Modules/Cadastro/module.json';

        $registry = app(ModuleRegistry::class);
        $registry->register($manifest);
        $registry->boot();

        $this->assertTrue($registry->has('cadastro'));
        $this->assertSame('Gestão de Cadastros e Atendimentos', $registry->get('cadastro')['name']);
        $this->assertSame(
            'Modules\\Cadastro\\Providers\\CadastroServiceProvider',
            $registry->get('cadastro')['service_provider']
        );
    }
}
