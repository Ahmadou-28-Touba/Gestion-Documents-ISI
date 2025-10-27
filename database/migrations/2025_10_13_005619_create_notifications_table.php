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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utilisateur_id')->constrained()->onDelete('cascade');
            $table->string('type');
            $table->string('titre');
            $table->text('message');
            $table->json('donnees')->nullable();
            $table->boolean('lue')->default(false);
            $table->timestamp('date_lecture')->nullable();
            $table->timestamps();
            
            $table->index(['utilisateur_id', 'lue']);
            $table->index(['type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
