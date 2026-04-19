<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table): void {
            $table->timestamp('archived_at')->nullable()->after('last_read_message_id');
            $table->timestamp('deleted_at')->nullable()->after('archived_at');
        });
    }

    public function down(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table): void {
            $table->dropColumn(['archived_at', 'deleted_at']);
        });
    }
};
