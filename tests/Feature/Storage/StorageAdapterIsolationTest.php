<?php

namespace Tests\Feature\Storage;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Client;
use App\Models\Document;
use App\Services\Storage\StorageAdapterRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\AbstractTestCase;

/**
 * Verifies that storage infrastructure is:
 *  - Not initialized before authorization is checked.
 *  - Degrading gracefully when no adapter is enabled.
 *  - Using the registry / DI instead of static helpers.
 */
#[Group('storage-isolation')]
#[Group('authorization-order')]
class StorageAdapterIsolationTest extends AbstractTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([VerifyCsrfToken::class]);
        \App\Models\Setting::factory()->create();
    }

    #[Test]
    public function it_storage_registry_is_resolved_via_container()
    {
        /* Act */
        $registry = app(StorageAdapterRegistry::class);

        /* Assert */
        $this->assertInstanceOf(StorageAdapterRegistry::class, $registry);
    }

    #[Test]
    public function it_storage_registry_singleton_is_same_instance_each_resolution()
    {
        /* Act */
        $a = app(StorageAdapterRegistry::class);
        $b = app(StorageAdapterRegistry::class);

        /* Assert */
        $this->assertSame($a, $b);
    }

    #[Test]
    public function it_filesystem_middleware_returns_422_for_json_when_no_storage_enabled()
    {
        /* Arrange – no file integration configured */
        \App\Models\Integration::whereApiType('file')->delete();
        app(StorageAdapterRegistry::class)->reset();

        $client = Client::factory()->create();

        /* Act */
        $response = $this->json(
            'POST',
            route('document.upload', $client->external_id),
            []
        );

        /* Assert – 422 JSON response, not a redirect */
        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => __('File integration required for this action')]);
    }

    #[Test]
    public function it_unauthorized_user_upload_gets_403_before_storage_boots()
    {
        /* Arrange */
        $user   = \App\Models\User::factory()->create(); // no permissions
        $this->actingAs($user);
        $client = Client::factory()->create();

        /* Act */
        $response = $this->json(
            'POST',
            route('document.upload', $client->external_id),
            []
        );

        /* Assert – unauthorized response, before any storage initialization */
        $response->assertStatus(403);
    }
}
