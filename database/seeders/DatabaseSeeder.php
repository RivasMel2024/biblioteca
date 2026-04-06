<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Primero crear los roles y permisos
        $this->call([
            RoleSeeder::class,
            CategorySeeder::class,
        ]);

        // Crear un bibliotecario (idempotent)
        $bibliotecario = User::firstOrCreate(
            ['email' => 'bibliotecario@biblioteca.com'],
            [
                'name' => 'Bibliotecario Admin',
                'password' => bcrypt('password123'),
            ]
        );
        if (!$bibliotecario->hasRole('bibliotecario')) {
            $bibliotecario->assignRole('bibliotecario');
        }

        // Crear un estudiante (idempotent)
        $estudiante = User::firstOrCreate(
            ['email' => 'estudiante@biblioteca.com'],
            [
                'name' => 'Juan Estudiante',
                'password' => bcrypt('password123'),
            ]
        );
        if (!$estudiante->hasRole('estudiante')) {
            $estudiante->assignRole('estudiante');
        }

        // Crear un docente (idempotent)
        $docente = User::firstOrCreate(
            ['email' => 'docente@biblioteca.com'],
            [
                'name' => 'María Docente',
                'password' => bcrypt('password123'),
            ]
        );
        if (!$docente->hasRole('docente')) {
            $docente->assignRole('docente');
        }

        // Crear usuarios adicionales de prueba solo si hay menos de 10 usuarios
        if (User::count() < 10) {
            User::factory(7)->create()->each(function ($user) {
                if (!$user->hasAnyRole(['estudiante', 'docente'])) {
                    $user->assignRole(fake()->randomElement(['estudiante', 'docente']));
                }
            });
        }

        $this->call([
            BookSeeder::class,
        ]);
    }
}
