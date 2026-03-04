<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    /**
     * Determine whether the user can view any loans.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['bibliotecario', 'estudiante', 'docente']);
    }

    /**
     * Determine whether the user can view the loan.
     */
    public function view(User $user, Loan $loan): bool
    {
        return $user->hasAnyRole(['bibliotecario', 'estudiante', 'docente']);
    }

    /**
     * Determine whether the user can create loans.
     * Solo estudiantes y docentes pueden solicitar préstamos de libros.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['estudiante', 'docente']);
    }

    /**
     * Determine whether the user can update the loan.
     * Solo el bibliotecario puede modificar préstamos.
     */
    public function update(User $user, Loan $loan): bool
    {
        return $user->hasRole('bibliotecario');
    }

    /**
     * Determine whether the user can delete the loan.
     * Solo el bibliotecario puede eliminar préstamos.
     */
    public function delete(User $user, Loan $loan): bool
    {
        return $user->hasRole('bibliotecario');
    }

    /**
     * Determine whether the user can return a loan.
     * Estudiantes, docentes y bibliotecarios pueden devolver libros.
     */
    public function return(User $user, Loan $loan): bool
    {
        return $user->hasAnyRole(['bibliotecario', 'estudiante', 'docente']);
    }

    /**
     * Determine whether the user can restore the loan.
     */
    public function restore(User $user, Loan $loan): bool
    {
        return $user->hasRole('bibliotecario');
    }

    /**
     * Determine whether the user can permanently delete the loan.
     */
    public function forceDelete(User $user, Loan $loan): bool
    {
        return $user->hasRole('bibliotecario');
    }
}
