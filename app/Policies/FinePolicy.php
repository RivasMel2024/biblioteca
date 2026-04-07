<?php

namespace App\Policies;

use App\Models\Fine;
use App\Models\User;

class FinePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ver multas');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Fine $fine): bool
    {
        if (!$user->can('ver multas')) {
            return false;
        }

        // Estudiantes solo ven sus propias multas
        if ($user->hasRole('estudiante')) {
            return $fine->loan->user_id === $user->id;
        }

        return true;
    }

    /**
     * Determine whether the user can pay the fine.
     */
    public function pay(User $user, Fine $fine): bool
    {
        if (!$user->can('pagar multas')) {
            return false;
        }

        return $user->hasRole('estudiante') && $fine->loan->user_id === $user->id;
    }
}
