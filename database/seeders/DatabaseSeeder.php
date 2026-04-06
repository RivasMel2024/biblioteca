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

        // Crear un admin (idempotent)
        $admin = User::firstOrCreate(
            ['email' => 'admin@biblioteca.com'],
            [
                'name' => 'Administrador Sistema',
                'password' => bcrypt('password123'),
            ]
        );
        $admin->syncRoles(['admin']);

        // Crear un bibliotecario (idempotent)
        $bibliotecario = User::firstOrCreate(
            ['email' => 'bibliotecario@biblioteca.com'],
            [
                'name' => 'Bibliotecario',
                'password' => bcrypt('password123'),
            ]
        );
        $bibliotecario->syncRoles(['bibliotecario']);

        // Crear un estudiante (idempotent)
        $estudiante = User::firstOrCreate(
            ['email' => 'estudiante@biblioteca.com'],
            [
                'name' => 'Juan Estudiante',
                'password' => bcrypt('password123'),
            ]
        );
        $estudiante->syncRoles(['estudiante']);

        // Crear usuarios adicionales de prueba solo si hay menos de 10 usuarios
        if (User::count() < 10) {
            User::factory(7)->create()->each(function (User $user) {
                $user->syncRoles([fake()->randomElement(['estudiante', 'bibliotecario'])]);
            });
        }

        $this->call([
            BookSeeder::class,
        ]);
    }
}
