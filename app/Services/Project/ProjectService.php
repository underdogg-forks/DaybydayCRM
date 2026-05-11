<?php

namespace App\Services\Project;

use App\Models\Client;
use App\Models\Project;
use Carbon\Carbon;
use Ramsey\Uuid\Uuid;

class ProjectService
{
    public function create(array $validated, int $userId): ?Project
    {
        $client = Client::query()->where('external_id', $validated['client_external_id'] ?? '')->first();
        if (! $client) {
            return null;
        }

        return Project::query()->create([
            'title' => $validated['title'],
            'description' => clean($validated['description']),
            'user_assigned_id' => $validated['user_assigned_id'],
            'deadline' => Carbon::parse($validated['deadline']),
            'status_id' => $validated['status_id'],
            'user_created_id' => $userId,
            'external_id' => Uuid::uuid4()->toString(),
            'client_id' => $client->id,
        ]);
    }

    public function assign(Project $project, int $userAssignedId): void
    {
        $project->user_assigned_id = $userAssignedId;
        $project->save();
    }

    public function updateDeadline(Project $project, string $deadlineDate, ?string $deadlineTime): void
    {
        $project->deadline = $deadlineDate . ' ' . ($deadlineTime ?: '00:00') . ':00';
        $project->save();
    }
}
