<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\RiskZone;
use App\Models\User;

class RiskZonePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, RiskZone $riskZone): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true; // Any authenticated user can create
    }

    public function update(User $user, RiskZone $riskZone): bool
    {
        // Creator or validator can update
        return $user->id === $riskZone->created_by
            || $user->role === UserRole::Validator;
    }

    public function delete(User $user, RiskZone $riskZone): bool
    {
        return $user->role === UserRole::Validator;
    }

    public function validate(User $user, RiskZone $riskZone): bool
    {
        return $user->role === UserRole::Validator;
    }

    public function reject(User $user, RiskZone $riskZone): bool
    {
        return $user->role === UserRole::Validator;
    }
}
