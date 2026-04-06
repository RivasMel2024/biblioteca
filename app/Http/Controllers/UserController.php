<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of all users (Admin only)
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $users = User::with('roles')
            ->when($request->has('email'), function ($query) use ($request) {
                $query->where('email', 'like', '%' . $request->input('email') . '%');
            })
            ->when($request->has('name'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->input('name') . '%');
            })
            ->when($request->has('role'), function ($query) use ($request) {
                $query->whereHas('roles', function ($q) use ($request) {
                    $q->where('name', $request->input('role'));
                });
            })
            ->paginate();

        return response()->json(UserResource::collection($users));
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        $this->authorize('view', $user);

        return response()->json(new UserResource($user->load('roles')));
    }

    /**
     * Update the specified user (Admin only)
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $user->update($request->validated());

        // If role is provided, update user's role
        if ($request->has('role')) {
            $user->syncRoles([$request->input('role')]);
        }

        return response()->json([
            'message' => 'Usuario actualizado exitosamente.',
            'data' => new UserResource($user->load('roles')),
        ]);
    }

    /**
     * Delete the specified user (Admin only)
     */
    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        // Prevent deleting the last admin
        if ($user->hasRole('admin')) {
            $adminCount = User::role('admin')->count();
            if ($adminCount <= 1) {
                return response()->json([
                    'message' => 'No se puede eliminar el último administrador.',
                ], 403);
            }
        }

        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado exitosamente.',
        ]);
    }
}
