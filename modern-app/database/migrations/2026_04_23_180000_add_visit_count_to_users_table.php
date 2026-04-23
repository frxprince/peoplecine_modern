<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'visit_count')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unsignedBigInteger('visit_count')->default(0)->after('legacy_authorize');
            });
        }

        $sqliteSourcePath = (string) config('database.connections.sqlite_source.database');

        if ($sqliteSourcePath === '' || ! is_file($sqliteSourcePath) || ! Schema::connection('sqlite_source')->hasTable('memberx')) {
            return;
        }

        try {
            DB::connection('sqlite_source')
                ->table('memberx')
                ->select(['ID', 'Visited'])
                ->orderBy('ID')
                ->chunk(500, function ($rows): void {
                    foreach ($rows as $row) {
                        DB::table('users')
                            ->where('legacy_memberx_id', (int) $row->ID)
                            ->update([
                                'visit_count' => max(0, (int) ($row->Visited ?? 0)),
                            ]);
                    }
                });
        } catch (Throwable) {
            // Ignore snapshot backfill failures and keep the new column defaulted to zero.
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'visit_count')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('visit_count');
            });
        }
    }
};
