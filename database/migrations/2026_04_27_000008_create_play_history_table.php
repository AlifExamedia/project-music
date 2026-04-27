<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('play_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('song_id')->constrained()->cascadeOnDelete();
            $table->timestamp('played_at')->useCurrent();
            $table->timestamps();
            $table->index(['user_id', 'played_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('play_history');
    }
};
