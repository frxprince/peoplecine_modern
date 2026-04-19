<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('direct_message_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_blocked')->default(false);
            $table->boolean('is_muted')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'target_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('direct_message_preferences');
    }
};
