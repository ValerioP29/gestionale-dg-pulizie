<?php

namespace App\Policies;

use App\Models\DgAnomaly;
use App\Models\User;

class DgAnomalyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'supervisor', 'viewer']);
    }

    public function view(User $user, DgAnomaly $anomaly): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'supervisor']);
    }

    public function update(User $user, DgAnomaly $anomaly): bool
    {
        return $user->hasAnyRole(['admin', 'supervisor']);
    }

    public function delete(User $user, DgAnomaly $anomaly): bool
    {
        return $user->isRole('admin');
    }
}
