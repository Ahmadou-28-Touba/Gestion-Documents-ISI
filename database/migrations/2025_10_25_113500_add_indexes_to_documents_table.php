<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->index('type');
            $table->index('etudiant_id');
            $table->index('date_generation');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['etudiant_id']);
            $table->dropIndex(['date_generation']);
        });
    }
};
