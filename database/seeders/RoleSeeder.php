<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Roles
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $bibliotecario = Role::firstOrCreate(['name' => 'bibliotecario']);
        $estudiante = Role::firstOrCreate(['name' => 'estudiante']);

        // Migrar rol legacy vendedor -> bibliotecario
        /** @var Role|null $legacyRole */
        $legacyRole = Role::where('name', 'vendedor')->first();
        if ($legacyRole) {
            User::role('vendedor')->get()->each(function (User $user) {
                $user->syncRoles(['bibliotecario']);
            });

            $legacyRole->delete();
        }

        // Permisos usuarios
        $manageUsers = Permission::firstOrCreate(['name' => 'gestionar usuarios']);

        // Permisos catalogo
        $viewCategories = Permission::firstOrCreate(['name' => 'ver categorias']);
        $createCategories = Permission::firstOrCreate(['name' => 'crear categorias']);
        $editCategories = Permission::firstOrCreate(['name' => 'editar categorias']);
        $deleteCategories = Permission::firstOrCreate(['name' => 'eliminar categorias']);

        // Permisos libros/copias
        $viewBooks = Permission::firstOrCreate(['name' => 'ver libros']);
        $createBooks = Permission::firstOrCreate(['name' => 'crear libros']);
        $editBooks = Permission::firstOrCreate(['name' => 'editar libros']);
        $deleteBooks = Permission::firstOrCreate(['name' => 'eliminar libros']);

        // Permisos prestamos
        $viewLoans = Permission::firstOrCreate(['name' => 'ver prestamos']);
        $createLoans = Permission::firstOrCreate(['name' => 'crear prestamos']);
        $returnLoans = Permission::firstOrCreate(['name' => 'devolver prestamos']);

        // Permisos multas
        $viewFines = Permission::firstOrCreate(['name' => 'ver multas']);
        $payFines = Permission::firstOrCreate(['name' => 'pagar multas']);

        // Admin: solo administracion de usuarios
        $admin->syncPermissions([
            $manageUsers,
        ]);

        // Bibliotecario: sistema de prestamos, copias/libros y multas
        $bibliotecario->syncPermissions([
            $viewCategories,
            $createCategories,
            $editCategories,
            $deleteCategories,
            $viewBooks,
            $createBooks,
            $editBooks,
            $deleteBooks,
            $viewLoans,
            $createLoans,
            $returnLoans,
            $viewFines,
            $payFines,
        ]);

        // Estudiante: crear prestamos y pagar multas (ademas de consultar su info)
        $estudiante->syncPermissions([
            $viewCategories,
            $viewBooks,
            $createLoans,
            $viewLoans,
            $viewFines,
            $payFines,
        ]);
    }
}
