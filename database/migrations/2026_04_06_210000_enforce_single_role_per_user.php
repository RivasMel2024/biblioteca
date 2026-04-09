<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function removeDuplicateRoles(string $table, string $modelMorphKey, bool $teams, string $teamForeignKey): void
    {
        $groupBy = ['model_type', $modelMorphKey];

        if ($teams) {
            $groupBy[] = $teamForeignKey;
        }

        /** @var Collection<int, object> $duplicates */
        $duplicates = DB::table($table)
            ->select(array_merge($groupBy, [DB::raw('MAX(role_id) as keep_role_id')]))
            ->groupBy($groupBy)
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $query = DB::table($table)
                ->where('model_type', $duplicate->model_type)
                ->where($modelMorphKey, $duplicate->{$modelMorphKey})
                ->where('role_id', '!=', $duplicate->keep_role_id);

            if ($teams) {
                if ($duplicate->{$teamForeignKey} === null) {
                    $query->whereNull($teamForeignKey);
                } else {
                    $query->where($teamForeignKey, $duplicate->{$teamForeignKey});
                }
            }

            $query->delete();
        }
    }

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
            $this->removeDuplicateRoles($modelHasRolesTable, $modelMorphKey, true, $teamForeignKey);

            Schema::table($modelHasRolesTable, function (Blueprint $table) use ($teamForeignKey, $modelMorphKey) {
                $table->unique([$teamForeignKey, 'model_type', $modelMorphKey], 'model_has_roles_single_role_per_model');
            });

            return;
        }

        $this->removeDuplicateRoles($modelHasRolesTable, $modelMorphKey, false, $teamForeignKey);

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
