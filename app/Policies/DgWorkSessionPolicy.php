<?php

namespace App\Policies;

use App\Models\User;
use App\Models\DgWorkSession;

class DgWorkSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'supervisor', 'viewer']);
    }

    public function view(User $user, DgWorkSession $session): bool
    {
        if ($user->isRole('admin') || $user->isRole('hr') || $user->isRole('supervisor')) {
            return true;
        }
        return $user->id === $session->user_id;
    }

    public function create(User $user): bool
    {
        return $user->isRole('admin');
    }

    public function update(User $user, DgWorkSession $session): bool
    {
        return $user->hasAnyRole(['admin', 'supervisor']);
    }

    public function delete(User $user, DgWorkSession $session): bool
    {
        return $user->isRole('admin');
    }
}
