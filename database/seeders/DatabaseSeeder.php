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
        ]);

        // Crear un bibliotecario
        $bibliotecario = User::factory()->create([
            'name' => 'Bibliotecario Admin',
            'email' => 'bibliotecario@biblioteca.com',
            'password' => bcrypt('password123'),
        ]);
        $bibliotecario->assignRole('bibliotecario');

        // Crear un estudiante
        $estudiante = User::factory()->create([
            'name' => 'Juan Estudiante',
            'email' => 'estudiante@biblioteca.com',
            'password' => bcrypt('password123'),
        ]);
        $estudiante->assignRole('estudiante');

        // Crear un docente
        $docente = User::factory()->create([
            'name' => 'María Docente',
            'email' => 'docente@biblioteca.com',
            'password' => bcrypt('password123'),
        ]);
        $docente->assignRole('docente');

        // Crear usuarios adicionales de prueba
        User::factory(7)->create()->each(function ($user) {
            $user->assignRole(fake()->randomElement(['estudiante', 'docente']));
        });

        $this->call([
            BookSeeder::class,
        ]);
    }
}
