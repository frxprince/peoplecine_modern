<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('recent_visitors')) {
            return;
        }

        Schema::create('recent_visitors', function (Blueprint $table): void {
            $table->id();
            $table->string('visitor_key')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('last_visited_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recent_visitors');
    }
};
