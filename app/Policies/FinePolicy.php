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
        return $user->hasAnyRole(['bibliotecario', 'estudiante', 'docente']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Fine $fine): bool
    {
        // Estudiantes solo ven sus propias multas
        if ($user->hasRole('estudiante')) {
            return $fine->loan->user_id === $user->id;
        }

        return $user->hasAnyRole(['bibliotecario', 'docente']);
    }

    /**
     * Determine whether the user can pay the fine.
     */
    public function pay(User $user, Fine $fine): bool
    {
        // El estudiante propietario puede pagar su multa
        if ($user->hasRole('estudiante')) {
            return $fine->loan->user_id === $user->id;
        }

        // Bibliotecarios también pueden procesar pagos
        return $user->hasRole('bibliotecario');
    }
}
