<?php

namespace Tests\Core;

use App\Core\Application\Tenant\TenantBrandingService;
use App\Core\Application\Tenant\TenantSettingService;
use App\Core\Domain\Tenant\TenantContext;

class TenantConfigurationTest extends TestCase
{
    /** @test */
    public function branding_can_be_created_for_tenant(): void
    {
        $tenant = $this->createTenant('Brand Tenant');
        TenantContext::set($tenant->ulid);

        $svc = app(TenantBrandingService::class);
        $svc->upsert([
            'system_name' => 'Gestao de Cadastros e Atendimentos',
            'primary_color' => '#FF5733',
            'secondary_color' => '#333333',
            'accent_color' => '#0055FF',
            'document_footer' => 'Acme Corp — CNPJ 00.000.000/0001-00',
        ]);

        $branding = $svc->current();
        $this->assertNotNull($branding);
        $this->assertEquals('#FF5733', $branding->primary_color);
        $this->assertEquals('Acme Corp — CNPJ 00.000.000/0001-00', $branding->document_footer);
    }

    /** @test */
    public function branding_upsert_updates_existing_record(): void
    {
        $tenant = $this->createTenant('Brand Tenant 2');
        TenantContext::set($tenant->ulid);

        $svc = app(TenantBrandingService::class);
        $svc->upsert(['primary_color' => '#000000']);
        $svc->upsert(['primary_color' => '#FFFFFF']);

        $this->assertDatabaseCount('tenant_brandings', 1);
        $this->assertEquals('#FFFFFF', $svc->current()->primary_color);
    }

    /** @test */
    public function branding_is_isolated_per_tenant(): void
    {
        $tenantA = $this->createTenant('Brand A');
        $tenantB = $this->createTenant('Brand B');

        TenantContext::set($tenantA->ulid);
        $svc = app(TenantBrandingService::class);
        $svc->upsert(['primary_color' => '#AA0000']);

        TenantContext::set($tenantB->ulid);
        $this->assertNull($svc->current(), 'Tenant B must not see Tenant A branding.');
    }

    /** @test */
    public function settings_can_be_stored_and_retrieved(): void
    {
        $tenant = $this->createTenant('Settings Tenant');
        TenantContext::set($tenant->ulid);

        $svc = app(TenantSettingService::class);
        $svc->set('timezone', 'America/Sao_Paulo');
        $svc->set('locale', 'pt_BR');

        $this->assertEquals('America/Sao_Paulo', $svc->get('timezone'));
        $this->assertEquals('pt_BR', $svc->get('locale'));
    }

    /** @test */
    public function settings_return_default_when_key_not_found(): void
    {
        $tenant = $this->createTenant('Settings Tenant 2');
        TenantContext::set($tenant->ulid);

        $svc = app(TenantSettingService::class);
        $this->assertEquals('fallback', $svc->get('missing_key', 'fallback'));
    }

    /** @test */
    public function settings_can_be_forgotten(): void
    {
        $tenant = $this->createTenant('Settings Tenant 3');
        TenantContext::set($tenant->ulid);

        $svc = app(TenantSettingService::class);
        $svc->set('to_delete', 'value');
        $svc->forget('to_delete');

        $this->assertNull($svc->get('to_delete'));
    }

    /** @test */
    public function settings_are_isolated_per_tenant(): void
    {
        $tenantA = $this->createTenant('Settings A');
        $tenantB = $this->createTenant('Settings B');

        TenantContext::set($tenantA->ulid);
        $svc = app(TenantSettingService::class);
        $svc->set('secret', 'tenant-a-value');

        TenantContext::set($tenantB->ulid);
        $this->assertNull($svc->get('secret'), 'Tenant B must not see Tenant A settings.');
    }
}
