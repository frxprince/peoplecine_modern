<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class MigrateSqliteToMariadb extends Command
{
    protected $signature = 'peoplecine:migrate-sqlite-to-mariadb
        {--source= : Absolute path to the SQLite file to import}
        {--source-connection=sqlite_source : Laravel connection name for the SQLite source}
        {--target-connection= : Laravel connection name for the MariaDB target}
        {--chunk=500 : Number of rows to copy per batch}
        {--fresh : Rebuild the target schema with migrate:fresh before importing}';

    protected $description = 'Copy the PeopleCine application data from SQLite into MariaDB.';

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
        $sourceConnection = (string) $this->option('source-connection');
        $targetConnection = (string) ($this->option('target-connection') ?: config('database.default', 'mariadb'));
        $chunkSize = max(50, (int) $this->option('chunk'));
        $sourcePath = trim((string) $this->option('source'));
        $rebuildFresh = (bool) $this->option('fresh');

        if ($sourcePath !== '') {
            Config::set("database.connections.{$sourceConnection}.database", $sourcePath);
        }

        $resolvedSourcePath = (string) config("database.connections.{$sourceConnection}.database", '');

        if ($resolvedSourcePath === '' || ! is_file($resolvedSourcePath)) {
            $this->error("SQLite source file not found: {$resolvedSourcePath}");

            return self::FAILURE;
        }

        if ($sourceConnection === $targetConnection) {
            $this->error('The source and target database connections must be different.');

            return self::FAILURE;
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
            $this->warn('No matching application tables were found to migrate.');

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
                $this->line("Importing {$table} ({$totalRows} rows)...");

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
                    throw new RuntimeException("Imported {$copied} rows into {$table}, expected {$totalRows}.");
                }
            }
        } finally {
            $this->enableForeignKeyChecks($targetConnection);
        }

        $this->info("SQLite import into [{$targetConnection}] completed successfully.");

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
        DB::connection($targetConnection)->table($table)->truncate();
    }

    private function disableForeignKeyChecks(string $targetConnection): void
    {
        DB::connection($targetConnection)->statement('SET FOREIGN_KEY_CHECKS=0');
    }

    private function enableForeignKeyChecks(string $targetConnection): void
    {
        DB::connection($targetConnection)->statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
