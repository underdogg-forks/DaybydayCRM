<?php

namespace Tests\Unit\Services\Integration;

use App\Models\Integration;
use App\Services\Integration\IntegrationService;
use Tests\AbstractTestCase;

class IntegrationServiceTest extends AbstractTestCase
{
    public function test_it_creates_integration_when_no_matching_api_type_exists(): void
    {
        $service = app(IntegrationService::class);

        $integration = $service->storeOrUpdateByApiType([
            'api_type' => 'billing',
            'name' => 'Xero',
            'client_id' => 'client-123',
            'client_secret' => 'secret-123',
            'api_key' => 'key-123',
        ]);

        $this->assertInstanceOf(Integration::class, $integration);
        $this->assertDatabaseHas('integrations', [
            'id' => $integration->id,
            'api_type' => 'billing',
            'name' => 'Xero',
            'client_id' => 'client-123',
        ]);
    }

    public function test_it_updates_existing_integration_by_api_type(): void
    {
        $existing = Integration::query()->create([
            'api_type' => 'file',
            'name' => 'Dropbox',
            'client_id' => 'old-id',
            'client_secret' => 'old-secret',
            'api_key' => 'old-key',
        ]);

        $service = app(IntegrationService::class);
        $integration = $service->storeOrUpdateByApiType([
            'api_type' => 'file',
            'name' => 'GoogleDrive',
            'client_id' => 'new-id',
            'client_secret' => 'new-secret',
            'api_key' => 'new-key',
        ]);

        $this->assertSame($existing->id, $integration->id);
        $this->assertDatabaseHas('integrations', [
            'id' => $existing->id,
            'name' => 'GoogleDrive',
            'client_id' => 'new-id',
        ]);
        $this->assertSame(1, Integration::query()->where('api_type', 'file')->count());
    }
}
