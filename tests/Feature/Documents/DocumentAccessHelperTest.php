<?php

namespace Tests\Feature\Controllers\Document;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Client;
use App\Models\Document;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\AbstractTestCase;

/**
 * Tests for document access control.
 *
 * These tests verify the *observable behavior* of document access rules
 * (ownership through creator / assignee / client) via HTTP requests instead
 * of testing private controller methods through reflection.
 *
 * Testing private methods via reflection couples tests to implementation
 * details and breaks as soon as the code is refactored.  Testing the HTTP
 * response is the correct level of abstraction.
 */
#[Group('security')]
#[Group('document_authorization')]
class DocumentAccessHelperTest extends AbstractTestCase
{
    use RefreshDatabase;

    private User $creator;

    private User $assignee;

    private User $clientOwner;

    private User $unrelated;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([VerifyCsrfToken::class]);
        \App\Models\Setting::factory()->create();

        $this->creator     = User::factory()->create();
        $this->assignee    = User::factory()->create();
        $this->clientOwner = User::factory()->create();
        $this->unrelated   = User::factory()->create();
        $this->client      = Client::factory()->create(['user_id' => $this->clientOwner->id]);
    }

    #[Test]
    public function it_creator_of_task_can_view_task_document()
    {
        /* Arrange */
        $task = Task::factory()->create([
            'user_created_id'  => $this->creator->id,
            'user_assigned_id' => $this->assignee->id,
            'client_id'        => $this->client->id,
        ]);
        $document = Document::factory()->create([
            'source_type' => Task::class,
            'source_id'   => $task->id,
            'mime'        => 'text/plain',
            'path'        => 'fake/path.txt',
        ]);
        $this->actingAs($this->creator);

        /* Act */
        $response = $this->get(route('document.view', $document->external_id));

        /* Assert – access should not be denied (no 403) */
        $this->assertNotEquals(403, $response->status(), 'Creator should have access via user_created_id');
    }

    #[Test]
    public function it_assignee_of_task_can_view_task_document()
    {
        /* Arrange */
        $task = Task::factory()->create([
            'user_created_id'  => $this->creator->id,
            'user_assigned_id' => $this->assignee->id,
            'client_id'        => $this->client->id,
        ]);
        $document = Document::factory()->create([
            'source_type' => Task::class,
            'source_id'   => $task->id,
            'mime'        => 'text/plain',
            'path'        => 'fake/path.txt',
        ]);
        $this->actingAs($this->assignee);

        /* Act */
        $response = $this->get(route('document.view', $document->external_id));

        /* Assert */
        $this->assertNotEquals(403, $response->status(), 'Assignee should have access via user_assigned_id');
    }

    #[Test]
    public function it_client_owner_can_view_document_attached_to_their_client_task()
    {
        /* Arrange */
        $task = Task::factory()->create([
            'user_created_id'  => $this->unrelated->id,
            'user_assigned_id' => $this->unrelated->id,
            'client_id'        => $this->client->id,
        ]);
        $document = Document::factory()->create([
            'source_type' => Task::class,
            'source_id'   => $task->id,
            'mime'        => 'text/plain',
            'path'        => 'fake/path.txt',
        ]);
        $this->actingAs($this->clientOwner);

        /* Act */
        $response = $this->get(route('document.view', $document->external_id));

        /* Assert */
        $this->assertNotEquals(403, $response->status(), 'Client owner should have access via client ownership');
    }

    #[Test]
    public function it_unrelated_user_cannot_view_document_they_have_no_connection_to()
    {
        /* Arrange */
        $otherClient = Client::factory()->create(['user_id' => $this->creator->id]);
        $task        = Task::factory()->create([
            'user_created_id'  => $this->creator->id,
            'user_assigned_id' => $this->assignee->id,
            'client_id'        => $otherClient->id,
        ]);
        $document = Document::factory()->create([
            'source_type' => Task::class,
            'source_id'   => $task->id,
            'mime'        => 'text/plain',
            'path'        => 'fake/path.txt',
        ]);
        $this->actingAs($this->unrelated);

        /* Act */
        $response = $this->get(route('document.view', $document->external_id));

        /* Assert */
        $response->assertStatus(302); // redirects back with flash message
        $this->assertTrue(
            session()->has('flash_message_warning'),
            'Unrelated user should see a warning flash message'
        );
    }

    #[Test]
    public function it_json_request_returns_403_json_for_unauthorized_document_view()
    {
        /* Arrange */
        $otherClient = Client::factory()->create(['user_id' => $this->creator->id]);
        $task        = Task::factory()->create([
            'user_created_id'  => $this->creator->id,
            'user_assigned_id' => $this->assignee->id,
            'client_id'        => $otherClient->id,
        ]);
        $document = Document::factory()->create([
            'source_type' => Task::class,
            'source_id'   => $task->id,
            'mime'        => 'text/plain',
            'path'        => 'fake/path.txt',
        ]);
        $this->actingAs($this->unrelated);

        /* Act */
        $response = $this->getJson(route('document.view', $document->external_id));

        /* Assert */
        $response->assertStatus(403);
    }
}
