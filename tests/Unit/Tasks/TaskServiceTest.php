<?php

namespace Unit\Tasks;

use App\Models\Client;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use App\Services\Task\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\AbstractTestCase;

#[CoversClass(TaskService::class)]
class TaskServiceTest extends AbstractTestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_covers_task_service_methods(): void
    {
        $service = new TaskService();
        $user    = User::factory()->create();
        $client  = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        $status  = Status::factory()->create(['source_type' => Task::class]);

        $task = $service->create([
            'title'               => 'T',
            'description'         => 'D',
            'user_assigned_id'    => $user->id,
            'deadline'            => '2026-02-01 12:00:00',
            'status_id'           => $status->id,
            'client_external_id'  => $client->external_id,
            'project_external_id' => $project->external_id,
        ], $user->id);

        $service->assign($task, $user->id);
        $service->updateDeadline($task, '2026-02-03 13:00:00');

        $this->assertSame('2026-02-03', $task->fresh()->deadline->format('Y-m-d'));
    }
}
