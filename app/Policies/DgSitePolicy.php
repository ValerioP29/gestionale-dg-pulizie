<?php

namespace App\Policies;

use App\Models\User;
use App\Models\DgSite;

class DgSitePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'supervisor', 'viewer']);
    }

    public function view(User $user, DgSite $site): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'supervisor']);
    }

    public function update(User $user, DgSite $site): bool
    {
        // Ruoli base
        if (in_array($user->role, ['admin', 'supervisor'])) {
            return true;
        }

        // Viewer: solo su cantieri privati
        if ($user->role === 'viewer') {
            return $site->type === 'privato';
        }

        return false;
    }

    public function delete(User $user, DgSite $site): bool
{
    if ($user->role === 'admin') {
        return true;
    }

    if ($user->role === 'supervisor') {
            return true;
        }

    if ($user->role === 'viewer') {
        return $site->type === 'privato';
    }

    return false;
}

}
