<?php

use App\Models\Book;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ========================================
// UPDATE LIBRO 
// ========================================

// 1.1 - Usuario NO autenticado intenta actualizar libro
test('usuario no autenticado no puede actualizar libro', function () {
    // Arrange
    $libro = Book::factory()->create();
    
    $datosActualizados = [
        'title' => 'Nuevo titulo',
        'description' => 'Nueva descripción',
        'ISBN' => $libro->ISBN,
        'total_copies' => 10,
        'available_copies' => 5,
        'is_available' => true,
    ];
    
    // Act
    $response = $this->putJson("/api/v1/books/{$libro->id}", $datosActualizados);
    
    // Assert
    $response->assertStatus(401); // no autorizado
});

// 1.2 - Usuario autenticado SIN rol bibliotecario intenta actualizar
test('usuario sin rol bibliotecario no puede actualizar libro', function () {
    // Arrange
    $usuario = User::factory()->create(); // Usuario sin roles
    $libro = Book::factory()->create();
    
    $datosActualizados = [
        'title' => 'Nuevo titulo',
        'description' => 'Nueva descripción',
        'ISBN' => $libro->ISBN,
        'total_copies' => 10,
        'available_copies' => 5,
        'is_available' => true,
    ];
    
    // Act
    $response = $this->actingAs($usuario, 'sanctum')
                     ->putJson("/api/v1/books/{$libro->id}", $datosActualizados);
    
    // Assert
    $response->assertStatus(403); // prohibido
});

// 1.3 - Usuario autenticado CON rol bibliotecario actualiza libro exitosamente
test('bibliotecario puede actualizar un libro', function () {
    // Arrange
    $role = Role::firstOrCreate(['name' => 'bibliotecario']);
    $bibliotecario = User::factory()->create();
    $bibliotecario->assignRole('bibliotecario');
    
    $libro = Book::factory()->create([
        'title' => 'Título Original',
        'ISBN' => '1234567890',
    ]);
    
    $datosActualizados = [
        'title' => 'Título Actualizado',
        'description' => 'Descripción actualizada',
        'ISBN' => '0987654321',
        'total_copies' => 15,
        'available_copies' => 10,
        'is_available' => true,
    ];
    
    // Act
    $response = $this->actingAs($bibliotecario, 'sanctum')
                     ->putJson("/api/v1/books/{$libro->id}", $datosActualizados);
    
    // Assert
    $response->assertStatus(200);
    $response->assertJsonFragment(['message' => 'el libro fue actualizado exitosamente']);
    
    // Verificar que se actualizó en la base de datos
    $this->assertDatabaseHas('books', [
        'id' => $libro->id,
        'title' => 'Título Actualizado',
        'ISBN' => '0987654321',
        'total_copies' => 15,
        'available_copies' => 10,
    ]);
});

// 2.1 - Actualizar libro sin título (campo requerido) 
test('falla al actualizar libro sin título requerido', function () {
    // Arrange
    $role = Role::firstOrCreate(['name' => 'bibliotecario']);
    $bibliotecario = User::factory()->create();
    $bibliotecario->assignRole('bibliotecario');
    
    $libro = Book::factory()->create();
    
    $datosInvalidos = [
        // 'title' => falta intencionalmente
        'description' => 'Descripción',
        'ISBN' => $libro->ISBN,
        'total_copies' => 10,
        'available_copies' => 5,
        'is_available' => true,
    ];
    
    // Act
    $response = $this->actingAs($bibliotecario, 'sanctum')
                     ->putJson("/api/v1/books/{$libro->id}", $datosInvalidos);
    
    // Assert
    $response->assertStatus(422) // Unprocessable Entity
             ->assertJsonValidationErrors(['title']);
});

// 2.2 - Actualizar libro sin ISBN (campo requerido)
test('falla al actualizar libro sin ISBN requerido', function () {
    // Arrange
    $role = Role::firstOrCreate(['name' => 'bibliotecario']);
    $bibliotecario = User::factory()->create();
    $bibliotecario->assignRole('bibliotecario');
    
    $libro = Book::factory()->create();
    
    $datosInvalidos = [
        'title' => 'Título válido',
        'description' => 'Descripción',
        // 'ISBN' => falta intencionalmente
        'total_copies' => 10,
        'available_copies' => 5,
        'is_available' => true,
    ];
    
    // Act
    $response = $this->actingAs($bibliotecario, 'sanctum')
                     ->putJson("/api/v1/books/{$libro->id}", $datosInvalidos);
    
    // Assert
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['ISBN']);
});

// 2.3 - Actualizar con ISBN duplicado de otro libro
test('falla al actualizar con ISBN duplicado', function () {
    // Arrange
    $role = Role::firstOrCreate(['name' => 'bibliotecario']);
    $bibliotecario = User::factory()->create();
    $bibliotecario->assignRole('bibliotecario');
    
    // Crear dos libros
    $libroExistente = Book::factory()->create(['ISBN' => '1111111111']);
    $libroActualizar = Book::factory()->create(['ISBN' => '2222222222']);
    
    $datosInvalidos = [
        'title' => 'Título válido',
        'description' => 'Descripción',
        'ISBN' => '1111111111', // ISBN ya existe en otro libro
        'total_copies' => 10,
        'available_copies' => 5,
        'is_available' => true,
    ];
    
    // Act
    $response = $this->actingAs($bibliotecario, 'sanctum')
                     ->putJson("/api/v1/books/{$libroActualizar->id}", $datosInvalidos);
    
    // Assert
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['ISBN']);
});

// 2.4 - Falla cuando available_copies es mayor que total_copies 
test('falla cuando available_copies es mayor que total_copies', function () {
    // Arrange
    $role = Role::firstOrCreate(['name' => 'bibliotecario']);
    $bibliotecario = User::factory()->create();
    $bibliotecario->assignRole('bibliotecario');
    
    $libro = Book::factory()->create();
    
    $datosInvalidos = [
        'title' => 'Título válido',
        'description' => 'Descripción',
        'ISBN' => $libro->ISBN,
        'total_copies' => 5,
        'available_copies' => 10, // Mayor que total_copies
        'is_available' => true,
    ];
    
    // Act
    $response = $this->actingAs($bibliotecario, 'sanctum')
                     ->putJson("/api/v1/books/{$libro->id}", $datosInvalidos);
    
    // Assert
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['available_copies']);
});

// 2.5 - Falla con total_copies = 0 o negativo
test('falla al actualizar con total_copies cero o negativo', function () {
    // Arrange
    $role = Role::firstOrCreate(['name' => 'bibliotecario']);
    $bibliotecario = User::factory()->create();
    $bibliotecario->assignRole('bibliotecario');
    
    $libro = Book::factory()->create();
    
    $datosInvalidos = [
        'title' => 'Título válido',
        'description' => 'Descripción',
        'ISBN' => $libro->ISBN,
        'total_copies' => 0, // No permitido (min:1)
        'available_copies' => 0,
        'is_available' => true,
    ];
    
    // Act
    $response = $this->actingAs($bibliotecario, 'sanctum')
                     ->putJson("/api/v1/books/{$libro->id}", $datosInvalidos);
    
    // Assert
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['total_copies']);
});

// 2.6 - Falla con available_copies negativo
test('falla al actualizar con available_copies negativo', function () {
    // Arrange
    $role = Role::firstOrCreate(['name' => 'bibliotecario']);
    $bibliotecario = User::factory()->create();
    $bibliotecario->assignRole('bibliotecario');
    
    $libro = Book::factory()->create();
    
    $datosInvalidos = [
        'title' => 'Título válido',
        'description' => 'Descripción',
        'ISBN' => $libro->ISBN,
        'total_copies' => 10,
        'available_copies' => -1, // Negativo no permitido
        'is_available' => true,
    ];
    
    // Act
    $response = $this->actingAs($bibliotecario, 'sanctum')
                     ->putJson("/api/v1/books/{$libro->id}", $datosInvalidos);
    
    // Assert
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['available_copies']);
});

// 3.1 - Actualizar todos los campos correctamente 
test('actualiza correctamente todos los campos del libro', function () {
    // Arrange
    $role = Role::firstOrCreate(['name' => 'bibliotecario']);
    $bibliotecario = User::factory()->create();
    $bibliotecario->assignRole('bibliotecario');
    
    $libro = Book::factory()->create([
        'title' => 'Título Viejo',
        'description' => 'Descripción vieja',
        'ISBN' => '1111111111',
        'total_copies' => 5,
        'available_copies' => 2,
        'is_available' => false,
    ]);
    
    $datosActualizados = [
        'title' => 'Título nuevo',
        'description' => 'Descripción nueva y detallada',
        'ISBN' => '9999999999',
        'total_copies' => 25,
        'available_copies' => 20,
        'is_available' => true,
    ];
    
    // Act
    $response = $this->actingAs($bibliotecario, 'sanctum')
                     ->putJson("/api/v1/books/{$libro->id}", $datosActualizados);
    
    // Assert
    $response->assertStatus(200);
    
    // Verificar que TODOS los campos se actualizaron
    $libroActualizado = $libro->fresh();
    expect($libroActualizado->title)->toBe('Título nuevo');
    expect($libroActualizado->description)->toBe('Descripción nueva y detallada');
    expect($libroActualizado->ISBN)->toBe('9999999999');
    expect($libroActualizado->total_copies)->toBe(25);
    expect($libroActualizado->available_copies)->toBe(20);
    expect($libroActualizado->is_available)->toBeTrue();
});

// 3.2 - Actualizar manteniendo el mismo ISBN
test('puede actualizar libro manteniendo el mismo ISBN', function () {
    // Arrange
    $role = Role::firstOrCreate(['name' => 'bibliotecario']);
    $bibliotecario = User::factory()->create();
    $bibliotecario->assignRole('bibliotecario');
    
    $libro = Book::factory()->create(['ISBN' => '5555555555']);
    
    $datosActualizados = [
        'title' => 'Nuevo Título',
        'description' => 'Nueva descripción',
        'ISBN' => '5555555555', // Mismo ISBN (no debe dar error de duplicado)
        'total_copies' => 20,
        'available_copies' => 15,
        'is_available' => true,
    ];
    
    // Act
    $response = $this->actingAs($bibliotecario, 'sanctum')
                     ->putJson("/api/v1/books/{$libro->id}", $datosActualizados);
    
    // Assert
    $response->assertStatus(200);
    
    $this->assertDatabaseHas('books', [
        'id' => $libro->id,
        'ISBN' => '5555555555',
        'title' => 'Nuevo Título',
    ]);
});

// 3.3 - Actualizar solo algunos campos (actualización parcial)
test('puede actualizar solo el título y descripción', function () {
    // Arrange
    $role = Role::firstOrCreate(['name' => 'bibliotecario']);
    $bibliotecario = User::factory()->create();
    $bibliotecario->assignRole('bibliotecario');
    
    $libro = Book::factory()->create([
        'title' => 'Título original',
        'description' => 'Descripción original',
        'ISBN' => '7777777777',
        'total_copies' => 10,
        'available_copies' => 5,
        'is_available' => true,
    ]);
    
    // Solo actualizar algunos campos
    $datosActualizados = [
        'title' => 'Solo título actualizado',
        'description' => 'Solo descripción actualizada',
        'ISBN' => $libro->ISBN, // Se mantiene
        'total_copies' => $libro->total_copies, // Se mantiene
        'available_copies' => $libro->available_copies, // Se mantiene
        'is_available' => $libro->is_available, // Se mantiene
    ];
    
    // Act
    $response = $this->actingAs($bibliotecario, 'sanctum')
                     ->putJson("/api/v1/books/{$libro->id}", $datosActualizados);
    
    // Assert
    $response->assertStatus(200);
    
    $libroActualizado = $libro->fresh();
    expect($libroActualizado->title)->toBe('Solo título actualizado');
    expect($libroActualizado->description)->toBe('Solo descripción actualizada');
    // Verificar que los demás campos NO cambiaron
    expect($libroActualizado->ISBN)->toBe('7777777777');
    expect($libroActualizado->total_copies)->toBe(10);
    expect($libroActualizado->available_copies)->toBe(5);
});

// 4.1 - Actualizar libro que no existe 
test('falla al actualizar libro que no existe', function () {
    // Arrange
    $role = Role::firstOrCreate(['name' => 'bibliotecario']);
    $bibliotecario = User::factory()->create();
    $bibliotecario->assignRole('bibliotecario');
    
    $idLibroInexistente = 99999; // ID que no existe
    
    $datosActualizados = [
        'title' => 'Título',
        'description' => 'Descripción',
        'ISBN' => '1234567890',
        'total_copies' => 10,
        'available_copies' => 5,
        'is_available' => true,
    ];
    
    // Act
    $response = $this->actingAs($bibliotecario, 'sanctum')
                     ->putJson("/api/v1/books/{$idLibroInexistente}", $datosActualizados);
    
    // Assert
    $response->assertStatus(404); // Not Found
});
