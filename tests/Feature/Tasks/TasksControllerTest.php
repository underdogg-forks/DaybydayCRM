<?php

namespace Feature\Tasks;

use App\Models\Client;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\AbstractTestCase;

class TasksControllerTest extends AbstractTestCase
{
    use RefreshDatabase;

    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = Client::factory()->create();
    }

    #[Test]
    #[Group('junie_repaired')]
    public function can_create_task()
    {
        /* Arrange */
        $this->withPermissions(['task-create']);

        /* Act */
        $response = $this->withoutMiddleware()->json('POST', route('tasks.store'), [
            'title'              => 'Tasks test',
            'description'        => 'This is a description',
            'status_id'          => Status::factory()->create(['source_type' => Task::class])->id,
            'user_assigned_id'   => $this->user->id,
            'user_created_id'    => $this->user->id,
            'client_external_id' => $this->client->external_id,
            'deadline'           => '2020-01-01',
        ]);

        /* Assert */
        $response->assertOk();
        $tasks = Task::where('user_assigned_id', $this->user->id);

        $this->assertCount(1, $tasks->get());
        $this->assertEquals($response->getData()->task_external_id, $tasks->first()->external_id);
    }

    #[Test]
    public function it_can_add_project_on_task()
    {
        /* Arrange */
        $this->withPermissions(['task-update-linked-project']);

        $project = Project::factory()->create();
        $task    = Task::factory()->create();

        $this->assertNull($task->project_id);

        /* Act */
        $response = $this->withoutMiddleware()->json('POST', route('tasks.update.project', $task->external_id), [
            'project_external_id' => $project->external_id,
        ]);

        /* Assert */
        $response->assertOk();
        $this->assertNotNull($task->refresh()->project_id);
    }

    #[Test]
    public function it_can_update_assignee()
    {
        /* Arrange */
        $this->withPermissions(['can-assign-new-user-to-task']);

        $task = Task::factory()->create();
        $this->assertNotEquals($task->user_assigned_id, $this->user->id);

        /* Act */
        $response = $this->withoutMiddleware()->json('PATCH', route('task.update.assignee', $task->external_id), [
            'user_assigned_id' => $this->user->id,
        ]);

        /* Assert */
        $response->assertOk();
        $this->assertEquals($this->user->id, $task->refresh()->user_assigned_id);
    }

    #[Test]
    public function it_can_update_status()
    {
        /* Arrange */
        $task   = Task::factory()->create();
        $status = Status::factory()->create(['source_type' => Task::class]);

        $this->assertNotEquals($task->status_id, $status->id);

        $this->withPermissions(['task-update-status']);

        /* Act */
        $response = $this->withoutMiddleware()->json('PATCH', route('task.update.status', $task->external_id), [
            'status_id' => $status->id,
        ]);

        /* Assert */
        $response->assertOk();
        $this->assertEquals($task->refresh()->status_id, $status->id);
    }

    #[Test]
    public function it_can_update_deadline_for_task()
    {
        /* Arrange */
        $task = Task::factory()->create();

        $this->withPermissions(['task-update-deadline']);

        /* Act */
        $response = $this->withoutMiddleware()->json('PATCH', route('task.update.deadline', $task->external_id), [
            'deadline_date' => '2020-08-06',
            'deadline_time' => '00:00',
        ]);

        /* Assert */
        $response->assertOk();
        $this->assertSame(
            Carbon::parse('2020-08-06')->toISOString(),
            Carbon::parse($task->refresh()->deadline)->toISOString()
        );
    }

    #[Test]
    public function it_can_list_tasks()
    {
        /* Arrange */
        Task::factory()->create();

        /* Act */
        $error = $this->json('GET', route('tasks.data'))
            ->assertSuccessful()
            ->json('error');

        /* Assert */
        $this->assertNull($error);
    }
}
