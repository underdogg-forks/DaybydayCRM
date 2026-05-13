<?php

namespace Unit\Projects;

use App\Models\Client;
use App\Models\Project;
use App\Models\Status;
use App\Models\User;
use App\Services\Project\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\AbstractTestCase;

#[CoversClass(ProjectService::class)]
class ProjectServiceTest extends AbstractTestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_covers_project_service_methods(): void
    {
        $service = new ProjectService();
        $user    = User::factory()->create();
        $client  = Client::factory()->create();
        $status  = Status::factory()->create(['source_type' => Project::class]);

        $project = $service->create([
            'title'              => 'P',
            'description'        => 'D',
            'user_assigned_id'   => $user->id,
            'deadline'           => '2026-02-01 12:00:00',
            'status_id'          => $status->id,
            'client_external_id' => $client->external_id,
        ], $user->id);

        $this->assertNotNull($project);

        $service->assign($project, $user->id);
        $service->updateDeadline($project, '2026-03-01');

        $this->assertSame('2026-03-01', $project->fresh()->deadline->format('Y-m-d'));

        $this->assertNull($service->create([
            'client_external_id' => 'missing',
            'title'              => 'x',
            'description'        => 'x',
            'user_assigned_id'   => $user->id,
            'deadline'           => '2026-01-01',
            'status_id'          => $status->id,
        ], $user->id));
    }
}
