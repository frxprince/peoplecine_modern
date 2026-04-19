<?php

use App\Support\LegacyImport\LegacyFontTagParser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('name_color', 20)->nullable()->after('name');
        });

        DB::table('rooms')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->get()
            ->each(function (object $room): void {
                $parsed = LegacyFontTagParser::parse($room->name);

                DB::table('rooms')
                    ->where('id', $room->id)
                    ->update([
                        'name' => $parsed['text'] !== '' ? $parsed['text'] : $room->name,
                        'name_color' => $parsed['color'],
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('name_color');
        });
    }
};
