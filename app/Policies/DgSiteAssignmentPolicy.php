<?php

namespace App\Policies;

use App\Models\User;
use App\Models\DgSiteAssignment;

class DgSiteAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'supervisor', 'viewer']);
    }

    public function view(User $user, DgSiteAssignment $assignment): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'supervisor']);
    }

    public function update(User $user, DgSiteAssignment $assignment): bool
    {
        // supervisor può modificare solo assegnazioni non create da un admin
        if ($user->role === 'supervisor') {
            return in_array(optional($assignment->assignedBy)->role, ['supervisor', 'viewer', 'employee']);
        }

        // admin può modificare tutto
        return $user->role === 'admin';
    }

    public function delete(User $user, DgSiteAssignment $assignment): bool
    {
        // admin può sempre eliminare
        if ($user->role === 'admin') return true;

        // supervisor solo se l’ha creata lui
        if ($user->role === 'supervisor') {
            return $assignment->assigned_by === $user->id;
        }

        return false;
    }
}
