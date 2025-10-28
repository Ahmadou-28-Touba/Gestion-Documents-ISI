<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('classe_enseignant', function (Blueprint $table) {
            if (!Schema::hasColumn('classe_enseignant', 'matiere')) {
                $table->string('matiere', 100)->nullable()->after('enseignant_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('classe_enseignant', function (Blueprint $table) {
            if (Schema::hasColumn('classe_enseignant', 'matiere')) {
                $table->dropColumn('matiere');
            }
        });
    }
};
