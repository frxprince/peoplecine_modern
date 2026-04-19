<?php

namespace App\Support\LegacyImport;

use Generator;
use RuntimeException;

class LegacyDumpReader
{
    private const TARGET_DATABASE = 'peopleci_wboard';

    /**
     * @param  array<int, string>  $columns
     * @return Generator<int, array<string, mixed>>
     */
    public function iterateRows(string $dumpPath, string $table, array $columns): Generator
    {
        if (! is_file($dumpPath)) {
            throw new RuntimeException("Legacy dump not found at {$dumpPath}");
        }

        $handle = fopen($dumpPath, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Unable to open dump file {$dumpPath}");
        }

        $insideTargetDatabase = false;
        $capturingStatement = false;
        $statement = '';
        $inString = false;
        $escaped = false;

        try {
            while (($line = fgets($handle)) !== false) {
                $trimmed = trim($line);

                if (preg_match('/^USE `([^`]+)`;$/', $trimmed, $matches) === 1) {
                    $insideTargetDatabase = $matches[1] === self::TARGET_DATABASE;

                    if (! $insideTargetDatabase && $capturingStatement) {
                        $capturingStatement = false;
                        $statement = '';
                    }

                    continue;
                }

                if (! $insideTargetDatabase) {
                    continue;
                }

                if (! $capturingStatement) {
                    if (str_starts_with($trimmed, "INSERT INTO `{$table}` VALUES ")) {
                        $capturingStatement = true;
                        $statement = $line;
                        $inString = false;
                        $escaped = false;

                        if ($this->hasUnquotedSemicolon($line, $inString, $escaped)) {
                            yield from $this->parseStatement($statement, $columns);
                            $capturingStatement = false;
                            $statement = '';
                        }
                    }

                    continue;
                }

                $statement .= $line;

                if ($this->hasUnquotedSemicolon($line, $inString, $escaped)) {
                    yield from $this->parseStatement($statement, $columns);
                    $capturingStatement = false;
                    $statement = '';
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  array<int, string>  $columns
     * @return Generator<int, array<string, mixed>>
     */
    private function parseStatement(string $statement, array $columns): Generator
    {
        $valuesPosition = stripos($statement, 'VALUES');

        if ($valuesPosition === false) {
            return;
        }

        $payload = trim(substr($statement, $valuesPosition + 6));
        $payload = rtrim($payload, ';');

        foreach ($this->extractTuples($payload) as $tuple) {
            $values = $this->parseTupleValues($tuple);

            if (count($values) !== count($columns)) {
                continue;
            }

            yield array_combine($columns, $values);
        }
    }

    /**
     * @return array<int, string>
     */
    private function extractTuples(string $payload): array
    {
        $tuples = [];
        $buffer = '';
        $depth = 0;
        $inString = false;
        $escaped = false;
        $length = strlen($payload);

        for ($index = 0; $index < $length; $index++) {
            $character = $payload[$index];

            if ($inString) {
                $buffer .= $character;

                if ($escaped) {
                    $escaped = false;
                } elseif ($character === '\\') {
                    $escaped = true;
                } elseif ($character === "'") {
                    $inString = false;
                }

                continue;
            }

            if ($character === "'") {
                $inString = true;
                $buffer .= $character;
                continue;
            }

            if ($character === '(') {
                if ($depth > 0) {
                    $buffer .= $character;
                }

                $depth++;
                continue;
            }

            if ($character === ')') {
                $depth--;

                if ($depth === 0) {
                    $tuples[] = $buffer;
                    $buffer = '';
                    continue;
                }

                $buffer .= $character;
                continue;
            }

            if ($depth > 0) {
                $buffer .= $character;
            }
        }

        return $tuples;
    }

    /**
     * @return array<int, mixed>
     */
    private function parseTupleValues(string $tuple): array
    {
        $values = [];
        $buffer = '';
        $inString = false;
        $escaped = false;
        $length = strlen($tuple);

        for ($index = 0; $index < $length; $index++) {
            $character = $tuple[$index];

            if ($inString) {
                $buffer .= $character;

                if ($escaped) {
                    $escaped = false;
                } elseif ($character === '\\') {
                    $escaped = true;
                } elseif ($character === "'") {
                    $inString = false;
                }

                continue;
            }

            if ($character === "'") {
                $inString = true;
                $buffer .= $character;
                continue;
            }

            if ($character === ',') {
                $values[] = $this->decodeValue($buffer);
                $buffer = '';
                continue;
            }

            $buffer .= $character;
        }

        $values[] = $this->decodeValue($buffer);

        return $values;
    }

    private function decodeValue(string $value): mixed
    {
        $trimmed = trim($value);

        if ($trimmed === 'NULL') {
            return null;
        }

        if ($trimmed === '') {
            return '';
        }

        if ($trimmed[0] === "'" && substr($trimmed, -1) === "'") {
            return stripcslashes(substr($trimmed, 1, -1));
        }

        if (is_numeric($trimmed)) {
            return str_contains($trimmed, '.') ? (float) $trimmed : (int) $trimmed;
        }

        return $trimmed;
    }

    private function hasUnquotedSemicolon(string $chunk, bool &$inString, bool &$escaped): bool
    {
        $length = strlen($chunk);

        for ($index = 0; $index < $length; $index++) {
            $character = $chunk[$index];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($character === '\\') {
                    $escaped = true;
                } elseif ($character === "'") {
                    $inString = false;
                }

                continue;
            }

            if ($character === "'") {
                $inString = true;
                continue;
            }

            if ($character === ';') {
                return true;
            }
        }

        return false;
    }
}
