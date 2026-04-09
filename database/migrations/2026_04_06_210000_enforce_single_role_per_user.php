<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $teams = (bool) config('permission.teams', false);
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');

        $modelHasRolesTable = $tableNames['model_has_roles'] ?? 'model_has_roles';
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';
        $teamForeignKey = $columnNames['team_foreign_key'] ?? 'team_id';

        if (!Schema::hasTable($modelHasRolesTable)) {
            return;
        }

        if ($teams) {
            DB::statement("DELETE m1 FROM {$modelHasRolesTable} m1 INNER JOIN {$modelHasRolesTable} m2 ON m1.{$teamForeignKey} = m2.{$teamForeignKey} AND m1.model_type = m2.model_type AND m1.{$modelMorphKey} = m2.{$modelMorphKey} AND m1.role_id < m2.role_id");

            Schema::table($modelHasRolesTable, function (Blueprint $table) use ($teamForeignKey, $modelMorphKey) {
                $table->unique([$teamForeignKey, 'model_type', $modelMorphKey], 'model_has_roles_single_role_per_model');
            });

            return;
        }

        DB::statement("DELETE m1 FROM {$modelHasRolesTable} m1 INNER JOIN {$modelHasRolesTable} m2 ON m1.model_type = m2.model_type AND m1.{$modelMorphKey} = m2.{$modelMorphKey} AND m1.role_id < m2.role_id");

        Schema::table($modelHasRolesTable, function (Blueprint $table) use ($modelMorphKey) {
            $table->unique(['model_type', $modelMorphKey], 'model_has_roles_single_role_per_model');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $modelHasRolesTable = $tableNames['model_has_roles'] ?? 'model_has_roles';

        if (!Schema::hasTable($modelHasRolesTable)) {
            return;
        }

        Schema::table($modelHasRolesTable, function (Blueprint $table) {
            $table->dropUnique('model_has_roles_single_role_per_model');
        });
    }
};
