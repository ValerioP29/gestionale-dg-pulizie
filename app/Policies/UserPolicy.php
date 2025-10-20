<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        // tutti tranne i dipendenti possono accedere alla lista utenti
        return in_array($user->role, ['admin', 'supervisor', 'viewer']);
    }

    public function view(User $user, User $target): bool
    {
        // i viewer possono solo vedere i dipendenti
        if ($user->role === 'viewer') {
            return $target->role === 'employee';
        }

        // admin e supervisor vedono tutto
        return in_array($user->role, ['admin', 'supervisor']);
    }

    public function create(User $user): bool
    {
        // solo admin e supervisor possono creare utenti
        return in_array($user->role, ['admin', 'supervisor']);
    }

    public function update(User $user, User $target): bool
    {
        // viewer può solo modificare dipendenti
        if ($user->role === 'viewer') {
            return $target->role === 'employee';
        }

        // supervisor NON può toccare admin né altri supervisor
        if ($user->role === 'supervisor') {
            return in_array($target->role, ['viewer', 'employee']);
        }

        // admin può modificare chiunque tranne se stesso
        if ($user->role === 'admin') {
            return $user->id !== $target->id;
        }

        return false;
    }

    public function delete(User $user, User $target): bool
    {
        // admin non può essere eliminato da nessuno
        if ($target->role === 'admin') {
            return false;
        }

        // supervisor può eliminare viewer o employee
        if ($user->role === 'supervisor') {
            return in_array($target->role, ['viewer', 'employee']);
        }

        // admin può eliminare chiunque tranne se stesso
        if ($user->role === 'admin') {
            return $user->id !== $target->id;
        }

        return false;
    }
}
