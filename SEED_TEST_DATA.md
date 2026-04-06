-- =====================================================
-- SCRIPT DE DATOS DE PRUEBA PARA BIBLIOTECA API
-- Ejecutar en la consola de Laravel: php artisan tinker
-- =====================================================

-- 1. CREAR USUARIOS DE PRUEBA
-- =====================================================

// Crear bibliotecario
User::factory()->create([
    'name' => 'Bibliotecario Principal',
    'email' => 'bibliotecario@example.com',
    'password' => Hash::make('password')
])->assignRole('bibliotecario');

// Crear estudiante 1
User::factory()->create([
    'name' => 'Juan Pérez (Estudiante)',
    'email' => 'estudiante@example.com',
    'password' => Hash::make('password')
])->assignRole('estudiante');

// Crear estudiante 2
User::factory()->create([
    'name' => 'María García (Estudiante)',
    'email' => 'maria@example.com',
    'password' => Hash::make('password')
])->assignRole('estudiante');

// Crear docente
User::factory()->create([
    'name' => 'Dr. Carlos López (Docente)',
    'email' => 'docente@example.com',
    'password' => Hash::make('password')
])->assignRole('docente');

-- 2. CREAR CATEGORÍAS
-- =====================================================

Category::create(['name' => 'Novela', 'description' => 'Novelas de ficción']);
Category::create(['name' => 'Ciencia Ficción', 'description' => 'Libros de ciencia ficción']);
Category::create(['name' => 'Misterio', 'description' => 'Novelas de misterio']);
Category::create(['name' => 'Historia', 'description' => 'Libros históricos']);
Category::create(['name' => 'Técnico', 'description' => 'Libros técnicos y de programación']);

-- 3. CREAR LIBROS CON COPIAS
-- =====================================================

// Libro 1: El Quijote (3 copias)
$book1 = Book::create([
    'title' => 'El Quijote',
    'description' => 'El ingenioso hidalgo Don Quixote de la Mancha',
    'ISBN' => '978-3-16-148410-0',
    'category_id' => 1,
    'total_copies' => 3,
    'available_copies' => 3,
    'is_available' => true,
]);

for ($i = 1; $i <= 3; $i++) {
    BookCopy::create([
        'book_id' => $book1->id,
        'barcode' => sprintf('BOOK-%06d-COPY-%03d', $book1->id, $i),
        'status' => 'DISPONIBLE',
    ]);
}

// Libro 2: Dune (2 copias)
$book2 = Book::create([
    'title' => 'Dune',
    'description' => 'Una novela de ciencia ficción de Frank Herbert',
    'ISBN' => '978-0-441-17271-9',
    'category_id' => 2,
    'total_copies' => 2,
    'available_copies' => 2,
    'is_available' => true,
]);

for ($i = 1; $i <= 2; $i++) {
    BookCopy::create([
        'book_id' => $book2->id,
        'barcode' => sprintf('BOOK-%06d-COPY-%03d', $book2->id, $i),
        'status' => 'DISPONIBLE',
    ]);
}

// Libro 3: Sherlock Holmes (4 copias)
$book3 = Book::create([
    'title' => 'Las Aventuras de Sherlock Holmes',
    'description' => 'Colección de historias de misterio',
    'ISBN' => '978-0-14-143951-8',
    'category_id' => 3,
    'total_copies' => 4,
    'available_copies' => 4,
    'is_available' => true,
]);

for ($i = 1; $i <= 4; $i++) {
    BookCopy::create([
        'book_id' => $book3->id,
        'barcode' => sprintf('BOOK-%06d-COPY-%03d', $book3->id, $i),
        'status' => 'DISPONIBLE',
    ]);
}

// Libro 4: Clean Code (2 copias)
$book4 = Book::create([
    'title' => 'Clean Code',
    'description' => 'A Handbook of Agile Software Craftsmanship',
    'ISBN' => '978-0-13-235088-4',
    'category_id' => 5,
    'total_copies' => 2,
    'available_copies' => 1,
    'is_available' => true,
]);

for ($i = 1; $i <= 2; $i++) {
    $status = $i === 1 ? 'PRESTADA' : 'DISPONIBLE';
    BookCopy::create([
        'book_id' => $book4->id,
        'barcode' => sprintf('BOOK-%06d-COPY-%03d', $book4->id, $i),
        'status' => $status,
    ]);
}

-- 4. CREAR PRÉSTAMOS DE EJEMPLO
-- =====================================================

// Préstamo activo (sin atraso)
$loan1 = Loan::create([
    'user_id' => 2, // Juan Pérez
    'book_copy_id' => 7, // Clean Code COPY-001
    'return_date' => now()->addDays(25),
    'returned_at' => null,
]);

// Préstamo completado sin multa
$loan2 = Loan::create([
    'user_id' => 3, // María García
    'book_copy_id' => 2, // Dune COPY-002
    'return_date' => now()->subDays(5),
    'returned_at' => now()->subDays(3), // Devolvió antes del vencimiento
]);

// Préstamo con atraso (genera multa automáticamente)
$loan3 = Loan::create([
    'user_id' => 4, // Carlos López (Docente)
    'book_copy_id' => 6, // Sherlock Holmes COPY-004
    'return_date' => now()->subDays(5), // Vencía hace 5 días
    'returned_at' => now(), // Devolviendo ahora (atrasado)
]);

-- 5. CREAR MULTA POR ATRASO (Solo si devolvió atrasado)
-- =====================================================

// La multa se crea automáticamente en ReturnLoanController
// Pero aquí creamos una de ejemplo si es necesario:

Fine::create([
    'loan_id' => 3,
    'days_overdue' => 5,
    'daily_amount' => 5000,
    'total_amount' => 25000,
    'status' => 'PENDIENTE',
    'paid_at' => null,
]);

-- =====================================================
-- PARA SALIR DE TINKER
-- =====================================================
// Escribe: exit
