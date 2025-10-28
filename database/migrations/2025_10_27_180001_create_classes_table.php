<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('filiere');
            $table->string('annee');
            $table->string('groupe')->nullable();
            $table->string('label')->nullable();
            $table->timestamps();
            $table->unique(['filiere', 'annee', 'groupe']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
