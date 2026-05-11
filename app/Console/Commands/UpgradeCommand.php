<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class UpgradeCommand extends Command
{
    protected $signature = 'daybyday:upgrade';

    protected $description = 'Safely upgrade DaybydayCRM - fix missing permissions and role assignments. Safe to run on production with existing users.';

    public function handle()
    {
        $this->info('🚀 Starting DaybydayCRM upgrade...');
        $this->newLine();

        $createdCount = $this->ensureAllPermissionsExist();
        $this->newLine();

        $syncedCount = $this->ensureRolesHaveAllPermissions();
        $this->newLine();

        $this->info('Upgrade complete!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Permissions Created', $createdCount],
                ['Permissions Synced to Roles', $syncedCount],
            ]
        );

        return 0;
    }

    /**
     * Ensure all permissions from PermissionName enum exist in database
     */
    private function ensureAllPermissionsExist(): int
    {
        $this->info('📋 Checking permissions...');

        $requiredPermissions = $this->getRequiredPermissions();
        $createdCount = 0;

        foreach ($requiredPermissions as $name => $data) {
            $exists = Permission::where('name', $name)->exists();

            if (!$exists) {
                Permission::create([
                    'external_id'  => Str::uuid()->toString(),
                    'display_name' => $data['display_name'],
                    'name'         => $name,
                    'description'  => $data['description'],
                    'grouping'     => $data['grouping'],
                ]);
                $this->line("   ✓ Created: {$name}");
                $createdCount++;
            }
        }

        if ($createdCount === 0) {
            $this->info('   All permissions already exist');
        }

        return $createdCount;
    }

    /**
     * Ensure owner and admin roles have all permissions
     */
    private function ensureRolesHaveAllPermissions(): int
    {
        $this->info('👥 Syncing permissions to roles...');

        $allPermissions = Permission::all()->pluck('id')->toArray();
        $syncedCount = 0;

        $roles = Role::whereIn('name', ['owner', 'administrator'])->get();

        if ($roles->isEmpty()) {
            $this->warn('   ⚠ No owner or administrator roles found!');
            return 0;
        }

        foreach ($roles as $role) {
            $currentPerms = $role->perms()->pluck('id')->toArray();
            $missingPerms = array_diff($allPermissions, $currentPerms);

            if (!empty($missingPerms)) {
                $role->perms()->attach($missingPerms);
                $syncedCount += count($missingPerms);
                $this->line("   ✓ Added " . count($missingPerms) . " permissions to {$role->display_name} role");
            } else {
                $this->line("   ✓ {$role->display_name} role already has all permissions");
            }
        }

        return $syncedCount;
    }

    /**
     * Get all required permissions with their metadata
     */
    private function getRequiredPermissions(): array
    {
        return [
            // User Management
            'user-create' => [
                'display_name' => 'Create user',
                'description'  => 'Be able to create a new user',
                'grouping'     => 'user',
            ],
            'user-update' => [
                'display_name' => 'Update user',
                'description'  => "Be able to update a user's information",
                'grouping'     => 'user',
            ],
            'user-delete' => [
                'display_name' => 'Delete user',
                'description'  => 'Be able to delete a user',
                'grouping'     => 'user',
            ],
            'user-view' => [
                'display_name' => 'View user',
                'description'  => 'Be able to view users',
                'grouping'     => 'user',
            ],
            // Client Management
            'client-create' => [
                'display_name' => 'Create client',
                'description'  => 'Permission to create client',
                'grouping'     => 'client',
            ],
            'client-update' => [
                'display_name' => 'Update client',
                'description'  => 'Permission to update client',
                'grouping'     => 'client',
            ],
            'client-delete' => [
                'display_name' => 'Delete client',
                'description'  => 'Permission to delete client',
                'grouping'     => 'client',
            ],
            'client-view' => [
                'display_name' => 'View client',
                'description'  => 'Permission to view clients',
                'grouping'     => 'client',
            ],
            // Document Management
            'document-delete' => [
                'display_name' => 'Delete document',
                'description'  => 'Permission to delete a document associated with a client',
                'grouping'     => 'document',
            ],
            'document-upload' => [
                'display_name' => 'Upload document',
                'description'  => 'Be able to upload a document associated with a client',
                'grouping'     => 'document',
            ],
            'document-view' => [
                'display_name' => 'View document',
                'description'  => 'Permission to view documents',
                'grouping'     => 'document',
            ],
            // Task Management
            'task-create' => [
                'display_name' => 'Create task',
                'description'  => 'Permission to create task',
                'grouping'     => 'task',
            ],
            'task-update-status' => [
                'display_name' => 'Update task status',
                'description'  => 'Permission to update task status',
                'grouping'     => 'task',
            ],
            'task-update-deadline' => [
                'display_name' => 'Change task deadline',
                'description'  => 'Permission to update a tasks deadline',
                'grouping'     => 'task',
            ],
            'can-assign-new-user-to-task' => [
                'display_name' => 'Change assigned user',
                'description'  => 'Permission to change the assigned user on a task',
                'grouping'     => 'task',
            ],
            'task-update-linked-project' => [
                'display_name' => 'Changed linked project',
                'description'  => 'Be able to change the project which is linked to a task',
                'grouping'     => 'task',
            ],
            'task-upload-files' => [
                'display_name' => 'Upload files to task',
                'description'  => 'Allowed to upload files for a task',
                'grouping'     => 'task',
            ],
            'task-delete' => [
                'display_name' => 'Delete task',
                'description'  => 'Permission to delete a task',
                'grouping'     => 'task',
            ],
            'task-update-assignment' => [
                'display_name' => 'Update task assignment',
                'description'  => 'Permission to update task assignment',
                'grouping'     => 'task',
            ],
            // Invoice Management
            'modify-invoice-lines' => [
                'display_name' => 'Modify invoice lines on a invoice / task',
                'description'  => 'Permission to create and update invoice lines on task, and invoices',
                'grouping'     => 'invoice',
            ],
            'invoice-see' => [
                'display_name' => "See invoices and it's invoice lines",
                'description'  => "Permission to see invoices on customer, and it's associated task",
                'grouping'     => 'invoice',
            ],
            'invoice-send' => [
                'display_name' => 'Send invoices to clients',
                'description'  => 'Be able to set an invoice as send to an customer (Or Send it if billing integration is active)',
                'grouping'     => 'invoice',
            ],
            'invoice-pay' => [
                'display_name' => 'Set an invoice as paid',
                'description'  => 'Be able to set an invoice as paid or not paid',
                'grouping'     => 'invoice',
            ],
            // Lead Management
            'lead-create' => [
                'display_name' => 'Create lead',
                'description'  => 'Permission to create lead',
                'grouping'     => 'lead',
            ],
            'lead-update-status' => [
                'display_name' => 'Update lead status',
                'description'  => 'Permission to update lead status',
                'grouping'     => 'lead',
            ],
            'lead-update-deadline' => [
                'display_name' => 'Change lead deadline',
                'description'  => 'Permission to update a lead deadline',
                'grouping'     => 'lead',
            ],
            'can-assign-new-user-to-lead' => [
                'display_name' => 'Change assigned user',
                'description'  => 'Permission to change the assigned user on a lead',
                'grouping'     => 'lead',
            ],
            'lead-delete' => [
                'display_name' => 'Delete lead',
                'description'  => 'Permission to delete a lead',
                'grouping'     => 'lead',
            ],
            'lead-view' => [
                'display_name' => 'View lead',
                'description'  => 'Permission to view leads',
                'grouping'     => 'lead',
            ],
            // Project Management
            'project-create' => [
                'display_name' => 'Create project',
                'description'  => 'Permission to create project',
                'grouping'     => 'project',
            ],
            'project-update-status' => [
                'display_name' => 'Update project status',
                'description'  => 'Permission to update project status',
                'grouping'     => 'project',
            ],
            'project-update-deadline' => [
                'display_name' => 'Change project deadline',
                'description'  => 'Permission to update a projects deadline',
                'grouping'     => 'project',
            ],
            'can-assign-new-user-to-project' => [
                'display_name' => 'Change assigned user',
                'description'  => 'Permission to change the assigned user on a project',
                'grouping'     => 'project',
            ],
            'project-upload-files' => [
                'display_name' => 'Upload files to project',
                'description'  => 'Allowed to upload files for a project',
                'grouping'     => 'project',
            ],
            'project-update' => [
                'display_name' => 'Update project',
                'description'  => 'Permission to update project',
                'grouping'     => 'project',
            ],
            'project-delete' => [
                'display_name' => 'Delete project',
                'description'  => 'Permission to delete project',
                'grouping'     => 'project',
            ],
            'project-update-assignment' => [
                'display_name' => 'Update project assignment',
                'description'  => 'Permission to update project assignment',
                'grouping'     => 'project',
            ],
            // Payment Management
            'payment-create' => [
                'display_name' => 'Add payment',
                'description'  => 'Be able to add a new payment on a invoice',
                'grouping'     => 'payment',
            ],
            'payment-delete' => [
                'display_name' => 'Delete payment',
                'description'  => 'Be able to delete a payment',
                'grouping'     => 'payment',
            ],
            'payment-update' => [
                'display_name' => 'Update payment',
                'description'  => 'Be able to update a payment',
                'grouping'     => 'payment',
            ],
            // Calendar/Appointment
            'calendar-view' => [
                'display_name' => 'View calendar',
                'description'  => 'Be able to view the calendar for appointments',
                'grouping'     => 'appointment',
            ],
            'appointment-create' => [
                'display_name' => 'Add appointment',
                'description'  => 'Be able to create a new appointment for a user',
                'grouping'     => 'appointment',
            ],
            'appointment-edit' => [
                'display_name' => 'Edit appointment',
                'description'  => 'Be able to edit appointment such as times and title',
                'grouping'     => 'appointment',
            ],
            'appointment-delete' => [
                'display_name' => 'Delete appointment',
                'description'  => 'Be able to delete an appointment',
                'grouping'     => 'appointment',
            ],
            // Product Management
            'product-create' => [
                'display_name' => 'Add Product',
                'description'  => 'Be able to create an product',
                'grouping'     => 'product',
            ],
            'product-edit' => [
                'display_name' => 'Edit product',
                'description'  => 'Be able to edit an product',
                'grouping'     => 'product',
            ],
            'product-delete' => [
                'display_name' => 'Delete product',
                'description'  => 'Be able to delete an product',
                'grouping'     => 'product',
            ],
            // Offer Management
            'offer-create' => [
                'display_name' => 'Add offer',
                'description'  => 'Be able to create an offer',
                'grouping'     => 'offer',
            ],
            'offer-edit' => [
                'display_name' => 'Edit offer',
                'description'  => 'Be able to edit an offer',
                'grouping'     => 'offer',
            ],
            'offer-delete' => [
                'display_name' => 'Delete offer',
                'description'  => 'Be able to delete an offer',
                'grouping'     => 'offer',
            ],
            // Absence Management
            'absence-manage' => [
                'display_name' => 'Manage absence',
                'description'  => 'Be able to manage absence',
                'grouping'     => 'absence',
            ],
            'absence-view' => [
                'display_name' => 'View absence',
                'description'  => 'Be able to view absence',
                'grouping'     => 'absence',
            ],
        ];
    }
}

