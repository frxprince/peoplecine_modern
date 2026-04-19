<?php

use App\Support\LegacyImport\LegacyImporter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

Artisan::command('peoplecine:import-legacy {dumpPath?} {--fresh}', function (?string $dumpPath = null) {
    $defaultPath = base_path('../all_mysql_backup.sql/all_mysql_backup.sql');
    $rawPath = $dumpPath ?: $defaultPath;
    $path = realpath($rawPath) ?: $rawPath;

    $this->components->info("Importing legacy dump from {$path}");

    $summary = app(LegacyImporter::class)->import(
        dumpPath: $path,
        fresh: (bool) $this->option('fresh'),
        output: $this->output,
    );

    foreach ($summary as $label => $count) {
        $this->line(str_pad($label, 28).' '.$count);
    }
})->purpose('Import legacy PeopleCine data from the MySQL dump');
