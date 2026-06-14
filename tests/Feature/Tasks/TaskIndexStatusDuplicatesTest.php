<?php

namespace Tests\Feature\Tasks;

use App\Models\Status;
use App\Models\Task;
use Tests\AbstractTestCase;

class TaskIndexStatusDuplicatesTest extends AbstractTestCase
{
    /** @test */
    public function test_duplicates_are_removed_in_index_controller()
    {
        // Create duplicate statuses
        Status::factory()->create(['source_type' => Task::class, 'title' => 'Open']);
        Status::factory()->create(['source_type' => Task::class, 'title' => 'Open']);

        $response = $this->get(route('tasks.index'));

        $statuses = $response->viewData('statuses');

        // This will have 2 'Open' status if not fixed
        $this->assertEquals(1, $statuses->filter(fn ($status) => $status->title === 'Open')->count());
    }
}
