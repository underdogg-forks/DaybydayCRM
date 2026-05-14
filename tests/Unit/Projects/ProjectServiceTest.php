<?php

namespace Tests\Unit\Projects;

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
    public function it_creates_project_with_valid_data(): void
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
        $this->assertSame('P', $project->title);
        $this->assertSame($client->id, $project->client_id);
        $this->assertSame('2026-02-01', $project->deadline->format('Y-m-d'));
    }

    #[Test]
    public function it_assigns_user_to_project(): void
    {
        $service = new ProjectService();
        $user    = User::factory()->create();
        $project = Project::factory()->create();

        $service->assign($project, $user->id);

        $this->assertSame($user->id, $project->fresh()->user_assigned_id);
    }

    #[Test]
    public function it_updates_project_deadline(): void
    {
        $service = new ProjectService();
        $project = Project::factory()->create();

        $service->updateDeadline($project, '2026-03-01');

        $this->assertSame('2026-03-01', $project->fresh()->deadline->format('Y-m-d'));
    }

    #[Test]
    public function it_returns_null_when_client_external_id_missing(): void
    {
        $service = new ProjectService();
        $user    = User::factory()->create();
        $status  = Status::factory()->create(['source_type' => Project::class]);

        $result = $service->create([
            'client_external_id' => 'missing',
            'title'              => 'x',
            'description'        => 'x',
            'user_assigned_id'   => $user->id,
            'deadline'           => '2026-01-01',
            'status_id'          => $status->id,
        ], $user->id);

        $this->assertNull($result);
    }

    #[Test]
    public function it_returns_null_when_client_not_found(): void
    {
        $service = new ProjectService();
        $user    = User::factory()->create();
        $status  = Status::factory()->create(['source_type' => Project::class]);

        $result = $service->create([
            'client_external_id' => 'nonexistent-external-id',
            'title'              => 'x',
            'description'        => 'x',
            'user_assigned_id'   => $user->id,
            'deadline'           => '2026-01-01',
            'status_id'          => $status->id,
        ], $user->id);

        $this->assertNull($result);
    }
}
