<?php

namespace Tests\Feature\Controllers\Integration;

use App\Models\Integration;
use Tests\AbstractTestCase;

class IntegrationsControllerTest extends AbstractTestCase
{
    public function test_store_persists_only_validated_fields_and_redirects_ok(): void
    {
        $payload = [
            'api_type' => 'billing',
            'name' => 'Xero',
            'client_id' => 'client',
            'client_secret' => 'secret',
            'api_key' => 'key',
            'is_admin' => true,
        ];

        $response = $this->post(route('integrations.store'), $payload);

        $response->assertOk();
        $this->assertDatabaseHas('integrations', [
            'api_type' => 'billing',
            'name' => 'Xero',
            'client_id' => 'client',
        ]);

        $this->assertArrayNotHasKey('is_admin', Integration::query()->where('api_type', 'billing')->first()->getAttributes());
    }
}
