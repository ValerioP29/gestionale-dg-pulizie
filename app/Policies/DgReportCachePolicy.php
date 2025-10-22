<?php

namespace App\Policies;

use App\Models\User;
use App\Models\DgReportCache;

class DgReportCachePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'supervisor']);
    }

    public function view(User $user, DgReportCache $report): bool
    {
        if ($user->isRole('admin')) return true;
        if ($user->isRole('supervisor')) return true; // in futuro: limita ai suoi cantieri
        return false;
    }

    public function create(User $user): bool
    {
        return $user->isRole('admin');
    }

    public function update(User $user, DgReportCache $report): bool
    {
        return $user->isRole('admin');
    }

    public function delete(User $user, DgReportCache $report): bool
    {
        return $user->isRole('admin');
    }
}
