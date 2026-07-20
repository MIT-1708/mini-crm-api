<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class ReportPolicy
{
    /**
     * Determine whether the user can view the performance report.
     */
    public function view(User $user): bool
    {
        return true; // Scoping inside controller will restrict Reps to their own data.
    }
}
