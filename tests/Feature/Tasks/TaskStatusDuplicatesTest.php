<?php

namespace Tests\Feature\Tasks;

use App\Models\Status;
use App\Models\Task;
use Tests\AbstractTestCase;

class TaskStatusDuplicatesTest extends AbstractTestCase
{
    /** @test */
    /** @test */
    public function it_removes_duplicate_statuses_in_controller_response()
    {
        // Create duplicate statuses
        Status::factory()->create(['source_type' => Task::class, 'title' => 'Open']);
        Status::factory()->create(['source_type' => Task::class, 'title' => 'Open']);

        $response = $this->get(route('tasks.create'));

        $statuses = $response->viewData('statuses');

        // This will now have only 1 'Open' status
        $this->assertEquals(1, $statuses->filter(fn ($title) => $title === 'Open')->count());
    }
}
