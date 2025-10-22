<?php

namespace App\Policies;

use App\Models\User;
use App\Models\DgPunch;

class DgPunchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'supervisor', 'viewer']);
    }

    public function view(User $user, DgPunch $punch): bool
    {
        if ($user->isRole('admin')) return true;

        if ($user->isRole('supervisor')) {
            // Il supervisor può vedere timbrature dei cantieri che gestisce (future estensioni)
            return true;
        }

        if ($user->isRole('viewer')) return true;

        // Un dipendente può vedere solo le proprie timbrature
        return $user->id === $punch->user_id;
    }

    public function create(User $user): bool
    {
        return $user->isRole('admin');
    }

    public function update(User $user, DgPunch $punch): bool
    {
        return $user->hasAnyRole(['admin', 'supervisor']);
    }

    public function delete(User $user, DgPunch $punch): bool
    {
        return $user->isRole('admin');
    }
}
