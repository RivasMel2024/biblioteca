<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            // Eliminar columnas que ahora se calculan desde BookCopy
            if (Schema::hasColumn('books', 'total_copies')) {
                $table->dropColumn('total_copies');
            }
            if (Schema::hasColumn('books', 'available_copies')) {
                $table->dropColumn('available_copies');
            }
            if (Schema::hasColumn('books', 'is_available')) {
                $table->dropColumn('is_available');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->integer('total_copies')->default(0);
            $table->integer('available_copies')->default(0);
            $table->boolean('is_available')->default(true);
        });
    }
};
