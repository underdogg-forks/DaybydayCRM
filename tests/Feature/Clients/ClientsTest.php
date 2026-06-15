<?php

namespace Tests\Feature\Clients;

use App\Enums\PermissionName;
use App\Http\Controllers\ClientsController;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Industry;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use App\Services\Client\ClientService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\AbstractTestCase;

#[CoversClass(ClientsController::class)]
class ClientsTest extends AbstractTestCase
{
    use RefreshDatabase;

    private Client $client;

    private User $userWithPermission;

    private User $userWithoutPermission;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2024-01-15 12:00:00');

        $this->client                = Client::factory()->create();
        $this->userWithPermission    = User::factory()->create();
        $this->userWithoutPermission = User::factory()->create();

        Setting::query()->firstOrCreate(
            ['id' => 1],
            [
                'client_number'  => 10000,
                'invoice_number' => 10000,
                'country'        => 'US',
                'company'        => 'Test Company',
                'max_users'      => 10,
                'vat'            => 0,
            ]
        );

        $this->withoutMiddleware(VerifyCsrfToken::class);

        // Flush query log to ensure clean state
        DB::flushQueryLog();
    }

    protected function tearDown(): void
    {
        // Disable query logging and flush to prevent memory leaks
        DB::disableQueryLog();
        DB::flushQueryLog();
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function it_lists_clients_on_index_page(): void
    {
        $response = $this->get(route('clients.index'));

        $response->assertStatus(200);
        $response->assertViewIs('clients.index');
    }

    #[Test]
    public function it_can_view_client_show_page(): void
    {
        /* Arrange */
        $client = Client::factory()->create();

        /* Act */
        $response = $this->get(route('clients.show', $client->external_id));

        /* Assert */
        $response->assertStatus(200);
        $response->assertViewIs('clients.show');
        $response->assertViewHas('client');
    }

    #[Test]
    public function it_loads_task_datatable_without_n_plus_1_queries()
    {
        /* Arrange */
        $this->user = User::factory()->withRole('employee')->create();
        $this->withPermissions(PermissionName::CLIENT_VIEW, PermissionName::TASK_VIEW);
        $this->user = $this->user->fresh();

        $industry     = Industry::factory()->create();
        $assignedUser = User::factory()->create();

        $client = Client::factory()->create([
            'industry_id' => $industry->id,
            'user_id'     => $this->user->id,
        ]);

        $taskStatus = Status::factory()->create([
            'source_type' => Task::class,
        ]);

        // Create 20 tasks
        Task::factory()->count(20)->create([
            'client_id'        => $client->id,
            'user_assigned_id' => $assignedUser->id,
            'status_id'        => $taskStatus->id,
        ]);

        /* Act */
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($this->user)->get(route('clients.taskDataTable', $client->external_id));

        $queryCount = count(DB::getQueryLog());

        /* Assert */
        $response->assertStatus(200);

        // Should have minimal queries with proper eager loading
        // Query count assertion verifies assigned_user relationship is eager loaded to prevent N+1 queries
        $this->assertLessThan(
            10,
            $queryCount,
            "Expected less than 10 queries but got {$queryCount}. This indicates an N+1 problem in task datatable."
        );
    }

    #[Test]
    public function it_loads_project_datatable_without_n_plus_1_queries()
    {
        /* Arrange */
        $this->user = User::factory()->withRole('employee')->create();
        $this->withPermissions(PermissionName::CLIENT_VIEW, PermissionName::PROJECT_VIEW);
        $this->user = $this->user->fresh();

        $industry     = Industry::factory()->create();
        $assignedUser = User::factory()->create();

        $client = Client::factory()->create([
            'industry_id' => $industry->id,
            'user_id'     => $this->user->id,
        ]);

        $projectStatus = Status::factory()->create([
            'source_type' => Project::class,
        ]);

        // Create 20 projects
        Project::factory()->count(20)->create([
            'client_id'        => $client->id,
            'user_assigned_id' => $assignedUser->id,
            'status_id'        => $projectStatus->id,
        ]);

        /* Act */
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($this->user)->get(route('clients.projectDataTable', $client->external_id));

        $queryCount = count(DB::getQueryLog());

        /* Assert */
        $response->assertStatus(200);

        // Should have minimal queries with proper eager loading
        // Query count assertion verifies assignee relationship is eager loaded to prevent N+1 queries
        $this->assertLessThan(
            10,
            $queryCount,
            "Expected less than 10 queries but got {$queryCount}. This indicates an N+1 problem in project datatable."
        );
    }

    #[Test]
    public function it_loads_lead_datatable_without_n_plus_1_queries()
    {
        /* Arrange */
        $this->user = User::factory()->withRole('employee')->create();
        $this->withPermissions(PermissionName::CLIENT_VIEW, PermissionName::LEAD_VIEW);
        $this->user = $this->user->fresh();

        $industry     = Industry::factory()->create();
        $assignedUser = User::factory()->create();

        $client = Client::factory()->create([
            'industry_id' => $industry->id,
            'user_id'     => $this->user->id,
        ]);

        $leadStatus = Status::factory()->create([
            'source_type' => Lead::class,
        ]);

        // Create 20 leads
        Lead::factory()->count(20)->create([
            'client_id'        => $client->id,
            'user_assigned_id' => $assignedUser->id,
            'status_id'        => $leadStatus->id,
        ]);

        /* Act */
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($this->user)->get(route('clients.leadDataTable', $client->external_id));

        $queryCount = count(DB::getQueryLog());

        /* Assert */
        $response->assertStatus(200);

        // Should have minimal queries with proper eager loading
        // Query count assertion verifies assigned_user relationship is eager loaded to prevent N+1 queries
        $this->assertLessThan(
            10,
            $queryCount,
            "Expected less than 10 queries but got {$queryCount}. This indicates an N+1 problem in lead datatable."
        );
    }

    #[Test]
    public function it_handles_large_client_load_efficiently()
    {
        /* Arrange */
        $this->user = User::factory()->withRole('employee')->create();
        $this->withPermissions(PermissionName::CLIENT_VIEW);
        $this->user = $this->user->fresh();

        $industry = Industry::factory()->create();

        // Create 100 clients with contacts to simulate realistic load
        $clients = Client::factory()
            ->count(100)
            ->create([
                'industry_id' => $industry->id,
                'user_id'     => $this->user->id,
            ]);

        foreach ($clients as $client) {
            Contact::factory()->create([
                'client_id'  => $client->id,
                'is_primary' => true,
            ]);
        }

        /* Act */
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($this->user)->get(route('clients.data'));

        $queryCount = count(DB::getQueryLog());

        /* Assert */
        $response->assertStatus(200);

        // Should be very few queries regardless of client count
        $this->assertLessThan(
            10,
            $queryCount,
            "Query count should not scale with number of clients. Got {$queryCount} queries for 100 clients."
        );
    }

    #[Test]
    public function it_can_create_client()
    {
        /* Arrange */
        $this->user = User::factory()->withRole('employee')->create();
        $this->withPermissions(PermissionName::CLIENT_CREATE);
        Setting::firstOrCreate(
            ['id' => 1],
            [
                'client_number'  => 10000,
                'invoice_number' => 10000,
                'country'        => 'US',
                'company'        => 'Test Company',
                'max_users'      => 10,
                'vat'            => 0,
                'currency'       => 'USD',
                'language'       => 'en',
            ]
        );
        $industry = Industry::factory()->create();
        $user     = User::factory()->create();

        /* Act */
        $response = $this->withHeaders([
            'Accept'           => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post(route('clients.store'), [
            'name'             => 'James Test',
            'email'            => 'james_' . uniqid('', true) . '@test.com',
            'primary_number'   => '2342342342',
            'secondary_number' => '423423432',
            'vat'              => '12312334',
            'company_name'     => 'James & Co',
            'address'          => 'james street',
            'zipcode'          => '2222',
            'city'             => 'Bond city',
            'company_type'     => 'Aps',
            'industry_id'      => $industry->id,
            'user_id'          => $user->id,
        ]);

        /* Assert */
        $this->assertEquals(201, $response->getStatusCode());
        $client   = Client::where('vat', '12312334')->first();
        $contacts = $client->contacts()->get();
        $this->assertCount(1, $contacts);
        $this->assertNotNull($client);
        $this->assertNotNull($client->contacts);
    }

    #[Test]
    public function it_can_open_edit_form_for_client(): void
    {
        /* Arrange */
        $client = Client::factory()->create();
        Contact::factory()->create(['client_id' => $client->id, 'is_primary' => true]);

        /* Act */
        $response = $this->get(route('clients.edit', $client->external_id));

        /* Assert */
        $response->assertStatus(200);
        $response->assertViewIs('clients.edit');
    }

    #[Test]
    public function it_can_open_edit_form_for_client_without_primary_contact(): void
    {
        /* Arrange */
        $client = Client::factory()->create();
        $client->contacts()->forceDelete();

        /* Act */
        $response = $this->get(route('clients.edit', $client->external_id));

        /* Assert */
        $response->assertStatus(200);
        $response->assertViewIs('clients.edit');
    }

    #[Test]
    public function it_can_update_client(): void
    {
        /* Arrange */
        $this->user = User::factory()->withRole('employee')->create();
        $this->withPermissions(PermissionName::CLIENT_UPDATE);
        $industry = Industry::factory()->create();
        $user     = User::factory()->create();
        $client   = Client::factory()->create([
            'vat'          => '5898989898',
            'company_type' => 'A/S',
            'company_name' => 'Hello',
            'industry_id'  => $industry->id,
            'user_id'      => $user->id,
        ]);
        Contact::factory()->create([
            'name'             => 'Kristian',
            'secondary_number' => '11111111',
            'primary_number'   => '2342342342',
            'client_id'        => $client->id,
            'is_primary'       => true,
        ]);

        /* Act */
        $response = $this->patch(route('clients.update', $client->external_id), [
            'name'             => 'Mads',
            'email'            => 'mads_' . uniqid('', true) . '@test.com',
            'primary_number'   => '2342342342',
            'secondary_number' => '423423432',
            'vat'              => '12312335',
            'company_name'     => 'Hello',
            'address'          => 'mads street',
            'zipcode'          => '2222',
            'city'             => 'Bond city',
            'company_type'     => 'Aps',
            'industry_id'      => $industry->id,
            'user_id'          => $user->id,
        ]);

        /* Assert */
        $response->assertStatus(302);
        $client = Client::where('vat', '12312335')->first();
        $this->assertNotNull($client, 'Client should exist with updated VAT number 12312335');
        $this->assertEquals('12312335', $client->vat);
        $this->assertEquals('Aps', $client->company_type);
        $this->assertEquals('Hello', $client->company_name);
        $this->assertEquals('2342342342', $client->primaryContact->primary_number);
        $this->assertEquals('423423432', $client->primaryContact->secondary_number);
        $this->assertEquals('Mads', $client->primaryContact->name);
        $this->assertNull(Client::where('vat', '5898989898')->first());
    }

    #[Test]
    public function it_can_update_assignee(): void
    {
        /* Arrange */
        $this->user = User::factory()->withRole('employee')->create();
        $this->withPermissions(PermissionName::CLIENT_UPDATE);
        $initialUser = User::factory()->create();
        $client      = Client::factory()->create(['user_id' => $initialUser->id]);
        $targetUser  = User::factory()->create();

        /* Act */
        $r = $this->post('/clients/updateassign/' . $client->external_id, [
            'user_external_id' => $targetUser->external_id,
        ]);

        /* Assert */
        $r->assertStatus(302);
        $r->assertSessionHas('flash_message', __('New user is assigned'));
        $this->assertEquals($targetUser->id, $client->refresh()->user_id);
    }

    #[Test]
    public function it_can_update_client_without_primary_contact(): void
    {
        /* Arrange */
        $this->user = User::factory()->withRole('employee')->create();
        $this->withPermissions(PermissionName::CLIENT_UPDATE);
        $industry = Industry::factory()->create();
        $user     = User::factory()->create();
        $client   = Client::factory()->create([
            'vat'          => '9999999999',
            'company_type' => 'A/S',
            'company_name' => 'NoPrimary Co',
        ]);
        $client->contacts()->forceDelete();

        /* Act */
        $response = $this->patch(route('clients.update', $client->external_id), [
            'name'             => 'No Contact Name',
            'email'            => 'noprimary_' . uniqid('', true) . '@test.com',
            'primary_number'   => '1234567890',
            'secondary_number' => '0987654321',
            'vat'              => '8888888888',
            'company_name'     => 'NoPrimary Co Updated',
            'address'          => 'no contact street',
            'zipcode'          => '1111',
            'city'             => 'Null City',
            'company_type'     => 'ApS',
            'industry_id'      => $industry->id,
            'user_id'          => $user->id,
        ]);

        /* Assert */
        $response->assertStatus(302);
        $response->assertSessionHas('flash_message', __('Client successfully updated'));
        $updatedClient = Client::where('vat', '8888888888')->first();
        $this->assertNotNull($updatedClient);
        $this->assertEquals('NoPrimary Co Updated', $updatedClient->company_name);
        $this->assertNull($updatedClient->primaryContact);
    }

    #[Test]
    public function it_cannot_update_assignee_without_permission(): void
    {
        /* Arrange */
        $client     = Client::factory()->create();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        /* Act */
        $response = $this->withHeaders([
            'Accept'           => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post('/clients/updateassign/' . $client->external_id, [
            'user_external_id' => $this->user->external_id,
        ]);

        /* Assert */
        $response->assertStatus(403);
        $this->assertNotEquals($this->user->id, $client->refresh()->user_id);
    }

    #[Test]
    public function it_can_delete_client_without_any_relations(): void
    {
        /* Arrange */
        $this->user = User::factory()->withRole('employee')->create();
        $this->withPermissions(PermissionName::CLIENT_DELETE);
        $client = Client::factory()->create();

        /* Act */
        $this->assertNotNull(Client::where('external_id', $client->external_id)->first());
        $r = $this->delete(route('clients.destroy', $client->external_id));

        /* Assert */
        $this->assertSoftDeleted($client);
    }

    #[Test]
    public function it_user_with_client_delete_permission_can_delete_client(): void
    {
        /* Arrange */
        $this->user = $this->userWithPermission;
        $this->withPermissions(PermissionName::CLIENT_DELETE);

        /* Act */
        $response = $this->delete(route('clients.destroy', $this->client->external_id));

        /* Assert */
        $response->assertStatus(302);
        $this->assertSoftDeleted('clients', ['id' => $this->client->id]);
    }

    #[Test]
    public function it_user_without_client_delete_permission_cannot_delete_client(): void
    {
        /* Arrange */
        $this->actingAs($this->userWithoutPermission);

        /* Act */
        $response = $this->withHeaders([
            'Accept'           => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->delete(route('clients.destroy', $this->client->external_id));

        /* Assert */
        $response->assertStatus(403);
        $this->assertDatabaseHas('clients', ['id' => $this->client->id, 'deleted_at' => null]);
    }

    #[Test]
    public function it_lists_clients_without_n_plus_1_queries(): void
    {
        /* Arrange */
        $this->user = User::factory()->withRole('employee')->create();
        $this->withPermissions(PermissionName::CLIENT_VIEW);
        $this->user = $this->user->fresh();

        $industry = Industry::factory()->create();

        // Create 50 clients to simulate load
        Client::factory()
            ->count(50)
            ->create([
                'industry_id' => $industry->id,
                'user_id'     => $this->user->id,
            ]);

        /* Act & Assert */
        // Flush and enable query logging
        DB::flushQueryLog();
        DB::enableQueryLog();
        DB::flushQueryLog();

        $response = $this->actingAs($this->user)->get(route('clients.data'));

        $queryCount = count(DB::getQueryLog());

        /* Assert */
        $response->assertStatus(200);

        // With proper eager loading, should be very few queries:
        // 1. Select clients
        // 2-3. Datatables internal queries
        // Should NOT be 50+ queries (one per client)
        $this->assertLessThan(
            10,
            $queryCount,
            "Expected less than 10 queries but got {$queryCount}. This indicates an N+1 problem."
        );
    }

    #[Test]
    public function it_shows_client_detail_without_n_plus_1_queries(): void
    {
        /* Arrange */
        $this->user = User::factory()->withRole('employee')->create();
        $this->withPermissions(PermissionName::CLIENT_VIEW);
        $this->user = $this->user->fresh();

        $industry     = Industry::factory()->create();
        $assignedUser = User::factory()->create();

        $client = Client::factory()->create([
            'industry_id' => $industry->id,
            'user_id'     => $assignedUser->id,
        ]);

        Contact::factory()->create([
            'client_id'  => $client->id,
            'is_primary' => true,
        ]);

        /* Act */
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($this->user)->get(route('clients.show', $client->external_id));

        $queryCount = count(DB::getQueryLog());

        /* Assert */
        $response->assertStatus(200);

        // With proper eager loading, should be minimal queries:
        // 1. Get client
        // 2. Get related data (invoices, users, etc)
        // Should NOT have dozens of separate queries
        $this->assertLessThan(
            20,
            $queryCount,
            "Expected less than 20 queries but got {$queryCount}. This indicates an N+1 problem in client detail view."
        );
    }

    #[Test]
    public function it_denies_unauthorized_user_from_listing_clients(): void
    {
        /* Arrange – user with no permissions */
        $this->user = User::factory()->create(['email' => 'noperms_' . uniqid('', true) . '@test.com']);
        $this->actingAs($this->user);

        /* Act */
        $response = $this->get(route('clients.index'));

        /* Assert */
        $response->assertForbidden();
    }

    #[Test]
    public function it_denies_unauthorized_user_from_viewing_client(): void
    {
        /* Arrange – user with no permissions */
        $client     = Client::factory()->create();
        $this->user = User::factory()->create(['email' => 'noperms_' . uniqid('', true) . '@test.com']);
        $this->actingAs($this->user);

        /* Act */
        $response = $this->get(route('clients.show', $client->external_id));

        /* Assert */
        $response->assertForbidden();
    }

    #[Test]
    public function it_returns_404_for_nonexistent_client(): void
    {
        $response = $this->get(route('clients.show', 'this-id-does-not-exist'));

        $response->assertStatus(404);
    }

    #[Test]
    public function it_returns_web_error_and_early_returns_when_client_creation_fails(): void
    {
        /* Arrange */
        $this->user = User::factory()->withRole('employee')->create();
        $this->withPermissions(PermissionName::CLIENT_CREATE);
        $industry = Industry::factory()->create();
        $user     = User::factory()->create();
        $this->bindFailingClientService();

        /* Act */
        $response = $this->from(route('clients.create'))
            ->post(route('clients.store'), $this->validClientPayload($industry->id, $user->id));

        /* Assert */
        $response->assertStatus(302);
        $response->assertRedirect(route('clients.create'));
        $response->assertSessionHasErrors(['client']);
        $response->assertSessionHas('_old_input.name', 'James Test');
    }

    #[Test]
    public function it_returns_json_error_and_early_returns_when_client_creation_fails(): void
    {
        /* Arrange */
        $this->user = User::factory()->withRole('employee')->create();
        $this->withPermissions(PermissionName::CLIENT_CREATE);
        $industry = Industry::factory()->create();
        $user     = User::factory()->create();
        $this->bindFailingClientService();

        /* Act */
        $response = $this->withHeaders([
            'Accept'           => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post(route('clients.store'), $this->validClientPayload($industry->id, $user->id));

        /* Assert */
        $response->assertStatus(500);
        $response->assertJson([
            'message' => __('Client could not be created. Please try again.'),
        ]);
    }

    private function bindFailingClientService(): void
    {
        $this->app->instance(ClientService::class, new class () extends ClientService {
            public function createClientWithContact(array $data): array
            {
                throw new RuntimeException('Simulated client creation failure');
            }
        });
    }

    private function validClientPayload(int $industryId, int $userId): array
    {
        return [
            'name'             => 'James Test',
            'email'            => 'james_' . uniqid('', true) . '@test.com',
            'primary_number'   => '2342342342',
            'secondary_number' => '423423432',
            'vat'              => '12312334',
            'company_name'     => 'James & Co',
            'address'          => 'james street',
            'zipcode'          => '2222',
            'city'             => 'Bond city',
            'company_type'     => 'Aps',
            'industry_id'      => $industryId,
            'user_id'          => $userId,
        ];
    }
}
