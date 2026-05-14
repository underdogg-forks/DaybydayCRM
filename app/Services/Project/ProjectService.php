<?php

namespace App\Services\Project;

use App\Models\Client;
use App\Models\Project;
use Carbon\Carbon;

class ProjectService
{
    public function create(array $validated, int $userId): ?Project
    {
        if (empty($validated['client_external_id'])) {
            return null;
        }

        $client = Client::query()->where('external_id', $validated['client_external_id'])->first();
        if ( ! $client) {
            return null;
        }

        return Project::query()->create([
            'title'            => $validated['title'],
            'description'      => clean($validated['description']),
            'user_assigned_id' => $validated['user_assigned_id'],
            'deadline'         => Carbon::parse($validated['deadline'])->toDateString(),
            'status_id'        => $validated['status_id'],
            'user_created_id'  => $userId,
            'client_id'        => $client->id,
        ]);
    }

    public function assign(Project $project, int $userAssignedId): void
    {
        $project->user_assigned_id = $userAssignedId;
        $project->save();
    }

    public function updateDeadline(Project $project, string $deadlineDate): void
    {
        $project->deadline = Carbon::parse($deadlineDate)->toDateString();
        $project->save();
    }

    public function prepareShowCollaboratorsAndTasks(Project $project): array
    {
        $tasks = $project->tasks->filter(static fn ($task) => $task->user !== null)->values();

        $collaborators = collect([$project->assignee]);
        foreach ($tasks as $task) {
            $collaborators->push($task->user);
        }

        return [
            'tasks'         => $tasks,
            'collaborators' => $collaborators
                ->reject(static fn ($collaborator) => $collaborator === null)
                ->unique('id')
                ->values(),
        ];
    }
}
