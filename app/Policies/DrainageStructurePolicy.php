<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\DrainageStructure;
use App\Models\User;

class DrainageStructurePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DrainageStructure $structure): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, DrainageStructure $structure): bool
    {
        return $user->id === $structure->created_by
            || $user->role === UserRole::Validator;
    }

    public function delete(User $user, DrainageStructure $structure): bool
    {
        return $user->role === UserRole::Validator;
    }

    public function validate(User $user, DrainageStructure $structure): bool
    {
        return $user->role === UserRole::Validator;
    }

    public function reject(User $user, DrainageStructure $structure): bool
    {
        return $user->role === UserRole::Validator;
    }
}
