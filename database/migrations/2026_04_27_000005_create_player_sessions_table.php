<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('current_song_id')->nullable()->constrained('songs')->nullOnDelete();
            $table->float('current_time')->default(0);
            $table->float('volume')->default(0.8);
            $table->boolean('muted')->default(false);
            $table->boolean('shuffle')->default(false);
            $table->string('loop')->default('none');
            $table->boolean('left_sidebar_open')->default(true);
            $table->boolean('right_sidebar_open')->default(false);
            $table->string('view_mode')->default('grid');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_sessions');
    }
};
