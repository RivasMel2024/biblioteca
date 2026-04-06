<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any users (Admin only)
     */
    public function viewAny(User $authUser): bool
    {
        return $authUser->hasRole('admin') && $authUser->can('gestionar usuarios');
    }

    /**
     * Determine whether the user can view a specific user
     */
    public function view(User $authUser, User $user): bool
    {
        return $authUser->hasRole('admin') && $authUser->can('gestionar usuarios');
    }

    /**
     * Determine whether the user can update a user (Admin only)
     */
    public function update(User $authUser, User $user): bool
    {
        return $authUser->hasRole('admin') && $authUser->can('gestionar usuarios');
    }

    /**
     * Determine whether the user can delete a user (Admin only)
     */
    public function delete(User $authUser, User $user): bool
    {
        // Prevent self-deletion
        if ($authUser->id === $user->id) {
            return false;
        }

        return $authUser->hasRole('admin') && $authUser->can('gestionar usuarios');
    }
}
