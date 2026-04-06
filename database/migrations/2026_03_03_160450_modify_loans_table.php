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
        Schema::table('loans', function (Blueprint $table) {
            // Add new columns
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('book_copy_id')->nullable()->constrained()->onDelete('set null');
            $table->dateTime('returned_at')->nullable();
            $table->dateTime('return_date'); // Fecha de devolución esperada (25 días por defecto)

            // Drop old columns if they exist and are not needed
            if (Schema::hasColumn('loans', 'requester_name')) {
                $table->dropColumn('requester_name');
            }
            if (Schema::hasColumn('loans', 'book_id')) {
                // We'll handle this separately if foreign key exists
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            if (Schema::hasColumn('loans', 'user_id')) {
                $table->dropForeignIdFor('users');
            }
            if (Schema::hasColumn('loans', 'book_copy_id')) {
                $table->dropForeignIdFor('book_copies');
            }
            if (Schema::hasColumn('loans', 'returned_at')) {
                $table->dropColumn('returned_at');
            }
            if (Schema::hasColumn('loans', 'return_date')) {
                $table->dropColumn('return_date');
            }

            // Restore old columns
            $table->string('requester_name')->nullable();
            $table->foreignId('book_id')->nullable()->constrained()->onDelete('set null');
        });
    }
};
