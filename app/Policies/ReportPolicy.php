<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Report $report): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Report $report): bool
    {
        return $user->role === UserRole::Validator;
    }

    public function validate(User $user, Report $report): bool
    {
        return $user->role === UserRole::Validator;
    }

    public function reject(User $user, Report $report): bool
    {
        return $user->role === UserRole::Validator;
    }
}
