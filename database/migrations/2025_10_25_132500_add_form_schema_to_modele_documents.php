<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modele_documents', function (Blueprint $table) {
            $table->json('form_schema')->nullable()->after('champs_requis');
        });
    }

    public function down(): void
    {
        Schema::table('modele_documents', function (Blueprint $table) {
            $table->dropColumn('form_schema');
        });
    }
};
