<?php

namespace App\Services\User;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UserUpdateService
{
    public function prepareValidatedInput(User $authenticatedUser, User $user, array $input, ?UploadedFile $imageFile): array
    {
        if ( ! $authenticatedUser->canChangePasswordOn($user)) {
            unset($input['password'], $input['password_confirmation']);
        }

        if (isset($input['password']) && $input['password'] !== '') {
            $input['password'] = bcrypt($input['password']);
        } else {
            unset($input['password']);
        }

        if ($imageFile !== null) {
            $companyExternalId = Setting::query()->first()->external_id;
            $input['image_path'] = Storage::put($companyExternalId, $imageFile);
        }

        return $input;
    }

    public function syncRoleAndDepartment(User $authenticatedUser, User $user, int $roleId, int $departmentId): bool
    {
        $owners = User::whereHas('roles', function ($query) {
            $query->where('name', Role::OWNER_ROLE);
        })->count();

        $currentRole = $user->roles->first();
        if ($currentRole && $currentRole->name == Role::OWNER_ROLE && $owners <= 1) {
            return false;
        }

        if ($authenticatedUser->canChangeRole()) {
            $user->roles()->sync([$roleId]);
        }

        $user->department()->sync([$departmentId]);

        return true;
    }
}
