<?php

namespace Database\Seeders;

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
        // Crear roles
        $bibliotecario = Role::firstOrCreate(['name' => 'bibliotecario']);
        $estudiante = Role::firstOrCreate(['name' => 'estudiante']);
        $docente = Role::firstOrCreate(['name' => 'docente']);

        // Crear permisos para libros
        $createBooks = Permission::firstOrCreate(['name' => 'crear libros']);
        $editBooks = Permission::firstOrCreate(['name' => 'editar libros']);
        $deleteBooks = Permission::firstOrCreate(['name' => 'eliminar libros']);
        $viewBooks = Permission::firstOrCreate(['name' => 'ver libros']);

        // Crear permisos para préstamos
        $createLoans = Permission::firstOrCreate(['name' => 'crear prestamos']);
        $viewLoans = Permission::firstOrCreate(['name' => 'ver prestamos']);
        $returnLoans = Permission::firstOrCreate(['name' => 'devolver prestamos']);

        // Asignar permisos al Bibliotecario (puede hacer todo)
        $bibliotecario->syncPermissions([
            $createBooks,
            $editBooks,
            $deleteBooks,
            $viewBooks,
            $viewLoans,
            $returnLoans,
        ]);

        // Asignar permisos al Estudiante (solo puede ver libros y crear préstamos)
        $estudiante->syncPermissions([
            $viewBooks,
            $createLoans,
            $viewLoans,
            $returnLoans,
        ]);

        // Asignar permisos al Docente (igual que estudiante)
        $docente->syncPermissions([
            $viewBooks,
            $createLoans,
            $viewLoans,
            $returnLoans,
        ]);
    }
}
