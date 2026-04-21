<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class MigrateMariadbToSqlite extends Command
{
    protected $signature = 'peoplecine:migrate-mariadb-to-sqlite
        {--source-connection= : Laravel connection name for the MariaDB source}
        {--target= : Absolute path to the SQLite file to create}
        {--target-connection=sqlite_source : Laravel connection name for the SQLite target}
        {--chunk=500 : Number of rows to copy per batch}
        {--fresh : Rebuild the target schema with migrate:fresh before exporting}';

    protected $description = 'Copy the PeopleCine application data from MariaDB into SQLite.';

    /**
     * @var array<int, string>
     */
    private array $skipTables = [
        'migrations',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'sessions',
    ];

    public function handle(): int
    {
        $sourceConnection = (string) ($this->option('source-connection') ?: config('database.default', 'mariadb'));
        $targetConnection = (string) $this->option('target-connection');
        $chunkSize = max(50, (int) $this->option('chunk'));
        $targetPath = trim((string) $this->option('target'));
        $rebuildFresh = (bool) $this->option('fresh');

        if ($targetPath === '') {
            $this->error('Please provide --target with an absolute path to the SQLite file.');

            return self::FAILURE;
        }

        $targetDirectory = dirname($targetPath);

        if ($targetDirectory === '' || $targetDirectory === '.' || (! is_dir($targetDirectory) && ! @mkdir($targetDirectory, 0775, true) && ! is_dir($targetDirectory))) {
            $this->error("Unable to create target directory: {$targetDirectory}");

            return self::FAILURE;
        }

        if ($sourceConnection === $targetConnection) {
            $this->error('The source and target database connections must be different.');

            return self::FAILURE;
        }

        Config::set("database.connections.{$targetConnection}.database", $targetPath);
        DB::purge($targetConnection);

        if (! file_exists($targetPath)) {
            touch($targetPath);
        }

        if ($rebuildFresh) {
            $this->info("Rebuilding target schema on [{$targetConnection}]...");

            Artisan::call('migrate:fresh', [
                '--database' => $targetConnection,
                '--force' => true,
            ]);

            $this->output->write(Artisan::output());
        }

        $source = DB::connection($sourceConnection);
        $target = DB::connection($targetConnection);

        $source->getPdo();
        $target->getPdo();

        $tables = $this->tablesToCopy($sourceConnection, $targetConnection);

        if ($tables->isEmpty()) {
            $this->warn('No matching application tables were found to export.');

            return self::SUCCESS;
        }

        $this->disableForeignKeyChecks($targetConnection);

        try {
            if (! $rebuildFresh) {
                foreach ($tables as $table) {
                    $this->truncateTargetTable($targetConnection, $table);
                }
            }

            foreach ($tables as $table) {
                $columns = Schema::connection($sourceConnection)->getColumnListing($table);

                if ($columns === []) {
                    continue;
                }

                $totalRows = (int) $source->table($table)->count();
                $this->line("Exporting {$table} ({$totalRows} rows)...");

                if ($totalRows === 0) {
                    continue;
                }

                $page = 1;
                $copied = 0;

                while (true) {
                    $query = $source->table($table)->select($columns);
                    $orderColumn = $columns[0] ?? null;

                    if ($orderColumn !== null) {
                        $query->orderBy($orderColumn);
                    }

                    $rows = $query->forPage($page, $chunkSize)->get();

                    if ($rows->isEmpty()) {
                        break;
                    }

                    $payload = $rows
                        ->map(fn (object $row): array => (array) $row)
                        ->all();

                    $target->table($table)->insert($payload);

                    $copied += count($payload);
                    $page++;
                }

                if ($copied !== $totalRows) {
                    throw new RuntimeException("Exported {$copied} rows into {$table}, expected {$totalRows}.");
                }
            }
        } finally {
            $this->enableForeignKeyChecks($targetConnection);
        }

        $sizeBytes = File::size($targetPath);
        $this->info("SQLite export completed successfully: {$targetPath} (".number_format($sizeBytes)." bytes)");

        return self::SUCCESS;
    }

    private function tablesToCopy(string $sourceConnection, string $targetConnection): Collection
    {
        $sourceTables = collect(Schema::connection($sourceConnection)->getTableListing())
            ->map(fn (string $table): string => $this->normalizeTableName($table));

        $targetTables = collect(Schema::connection($targetConnection)->getTableListing())
            ->map(fn (string $table): string => $this->normalizeTableName($table));

        return $sourceTables
            ->intersect($targetTables)
            ->reject(fn (string $table): bool => in_array($table, $this->skipTables, true))
            ->values();
    }

    private function normalizeTableName(string $table): string
    {
        $segments = explode('.', strtolower($table));

        return (string) end($segments);
    }

    private function truncateTargetTable(string $targetConnection, string $table): void
    {
        DB::connection($targetConnection)->table($table)->delete();
    }

    private function disableForeignKeyChecks(string $targetConnection): void
    {
        DB::connection($targetConnection)->statement('PRAGMA foreign_keys = OFF');
    }

    private function enableForeignKeyChecks(string $targetConnection): void
    {
        DB::connection($targetConnection)->statement('PRAGMA foreign_keys = ON');
    }
}
