<?php

namespace Feature\Projects;

use App\Models\Client;
use App\Models\Project;
use App\Models\Status;
use App\Models\User;
use Carbon\Carbon;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\AbstractTestCase;

class ProjectsControllerTest extends AbstractTestCase
{
    use RefreshDatabase;

    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();

        $this->client = Client::factory()->create();
    }

    #[Test]
    #[Group('junie_repaired')]
    public function can_create_project()
    {
        /* Arrange */
        $this->withPermissions(['project-create']);

        /* Act */
        $response = $this->withoutMiddleware()->json('POST', route('projects.store'), [
            'title'              => 'Projects test',
            'description'        => 'This is a description',
            'status_id'          => Status::factory()->create(['source_type' => Project::class])->id,
            'user_assigned_id'   => $this->user->id,
            'user_created_id'    => $this->user->id,
            'client_external_id' => $this->client->external_id,
            'deadline'           => '2020-01-01',
        ]);

        /* Assert */
        $projects = Project::where('user_assigned_id', $this->user->id);
        $this->assertCount(1, $projects->get());
        $this->assertEquals($response->getData()->project_external_id, $projects->first()->external_id);
    }

    #[Test]
    public function it_can_update_assignee()
    {
        /* Arrange */
        $project = Project::factory()->create();
        $this->assertNotEquals($project->user_assigned_id, $this->user->id);
        $this->withPermissions(['can-assign-new-user-to-project']);

        /* Act */
        $response = $this->withoutMiddleware()->json('PATCH', route('project.update.assignee', $project->external_id), [
            'user_assigned_id' => $this->user->id,
        ]);

        /* Assert */
        $response->assertStatus(302);
        $this->assertEquals($project->refresh()->user_assigned_id, $this->user->id);
    }

    #[Test]
    public function it_can_update_status()
    {
        /* Arrange */
        $project    = Project::factory()->create();
        $status     = Status::factory()->create(['source_type' => Project::class]);
        $this->assertNotEquals($project->status_id, $status->id);

        $this->withPermissions(['project-update-status']);

        /* Act */
        $response = $this->withoutMiddleware()->json('PATCH', route('project.update.status', $project->external_id), [
            'status_id' => $status->id,
        ]);

        /* Assert */
        $response->assertStatus(302);
        $this->assertEquals($status->id, $project->refresh()->status_id);
    }

    #[Test]
    public function it_can_update_deadline_for_project()
    {
        /* Arrange */
        $this->withoutExceptionHandling();
        $project    = Project::factory()->create();

        $this->withPermissions(['project-update-deadline']);

        /* Act */
        $response = $this->withoutMiddleware()->json('PATCH', route('project.update.deadline', $project->external_id), [
            'deadline_date' => '2020-08-06',
            'deadline_time' => '00:00',
        ]);

        /* Assert */
        $this->assertTrue($response->isRedirect(), 'Expected a redirect response');
        $this->assertFalse(session()->has('flash_message_warning'), 'Unexpected flash warning: ' . session('flash_message_warning'));
        $rawDeadline = DB::table('projects')->where('id', $project->id)->value('deadline');
        $expectedIso = Carbon::parse('2020-08-06 00:00:00')->toISOString();
        $this->assertEquals($expectedIso, Carbon::parse($rawDeadline)->toISOString(), 'Raw DB deadline mismatch');
        $this->assertEquals($expectedIso, $project->refresh()->deadline->toISOString());
    }
}
