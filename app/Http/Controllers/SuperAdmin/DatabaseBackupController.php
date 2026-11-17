<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseBackupController extends Controller
{
    /**
     * Show Database Backup & Table-wise Export Dashboard
     */
    public function index(Request $request)
    {
        $dbName = DB::connection()->getDatabaseName();

        $rawTables = DB::select("
            SELECT 
                TABLE_NAME as name, 
                ENGINE as engine, 
                TABLE_ROWS as table_rows, 
                DATA_LENGTH as data_length, 
                INDEX_LENGTH as index_length, 
                (DATA_LENGTH + INDEX_LENGTH) as total_size,
                CREATE_TIME as create_time, 
                UPDATE_TIME as update_time, 
                TABLE_COLLATION as collation, 
                TABLE_COMMENT as comment 
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = ? 
            ORDER BY TABLE_NAME ASC
        ", [$dbName]);

        $search = trim((string) $request->input('search', ''));

        $tables = collect($rawTables)->map(function ($t) {
            $dataBytes = (float) ($t->data_length ?? 0);
            $indexBytes = (float) ($t->index_length ?? 0);
            $totalBytes = (float) ($t->total_size ?? ($dataBytes + $indexBytes));
            $rows = (int) ($t->table_rows ?? 0);

            return [
                'name'         => $t->name,
                'engine'       => $t->engine ?? 'InnoDB',
                'rows'         => $rows,
                'data_size'    => $this->formatBytes($dataBytes),
                'index_size'   => $this->formatBytes($indexBytes),
                'total_size'   => $this->formatBytes($totalBytes),
                'total_bytes'  => $totalBytes,
                'collation'    => $t->collation ?? 'utf8mb4_unicode_ci',
                'update_time'  => $t->update_time ? date('d M Y, h:i A', strtotime($t->update_time)) : null,
                'create_time'  => $t->create_time ? date('d M Y, h:i A', strtotime($t->create_time)) : null,
            ];
        });

        $totalTablesCount = $tables->count();
        $totalDatabaseBytes = $tables->sum('total_bytes');
        $totalEstimatedRows = $tables->sum('rows');

        if ($search !== '') {
            $tables = $tables->filter(function ($t) use ($search) {
                return stripos($t['name'], $search) !== false;
            });
        }

        return view('superadmin.database.index', [
            'dbName'             => $dbName,
            'tables'             => $tables,
            'totalTablesCount'   => $totalTablesCount,
            'totalDatabaseSize'  => $this->formatBytes($totalDatabaseBytes),
            'totalEstimatedRows' => number_format($totalEstimatedRows),
            'search'             => $search,
        ]);
    }

    /**
     * Download Full Database or Specific Table (SQL / CSV / JSON)
     */
    public function download(Request $request)
    {
        $tableParam = trim((string) $request->input('table', ''));
        $tablesParam = $request->input('tables', []);
        $format = strtolower(trim((string) $request->input('format', 'sql')));
        $structureOnly = (bool) $request->input('structure_only', false);
        $dataOnly = (bool) $request->input('data_only', false);

        $dbName = DB::connection()->getDatabaseName();
        $allValidTables = collect(DB::select("
            SELECT TABLE_NAME as name 
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = ?
            ORDER BY TABLE_NAME ASC
        ", [$dbName]))->pluck('name')->toArray();

        // 1. Single Table Download
        if ($tableParam !== '' && $tableParam !== 'all' && $tableParam !== 'full') {
            if (!in_array($tableParam, $allValidTables, true)) {
                return redirect()->back()->with('error', "Table '{$tableParam}' not found in database.");
            }

            $dateSuffix = date('Y-m-d_H-i-s');

            if ($format === 'csv') {
                return $this->streamCsvExport($tableParam, "{$tableParam}_{$dateSuffix}.csv");
            } elseif ($format === 'json') {
                return $this->streamJsonExport($tableParam, "{$tableParam}_{$dateSuffix}.json");
            } else {
                return $this->streamSqlExport([$tableParam], "{$tableParam}_{$dateSuffix}.sql", $structureOnly, $dataOnly);
            }
        }

        // 2. Selected Multiple Tables Download
        if (!empty($tablesParam)) {
            if (is_string($tablesParam)) {
                $tablesParam = explode(',', $tablesParam);
            }
            $selectedTables = array_values(array_intersect($tablesParam, $allValidTables));
            if (empty($selectedTables)) {
                return redirect()->back()->with('error', 'No valid tables selected for download.');
            }

            $dateSuffix = date('Y-m-d_H-i-s');
            $filename = count($selectedTables) === 1 
                ? "{$selectedTables[0]}_{$dateSuffix}.sql" 
                : "{$dbName}_selected_tables_{$dateSuffix}.sql";

            return $this->streamSqlExport($selectedTables, $filename, $structureOnly, $dataOnly);
        }

        // 3. Full Database Download
        $dateSuffix = date('Y-m-d_H-i-s');
        $filename = "{$dbName}_full_backup_{$dateSuffix}.sql";

        return $this->streamSqlExport($allValidTables, $filename, $structureOnly, $dataOnly);
    }

    /**
     * Download selected tables via POST form
     */
    public function downloadSelected(Request $request)
    {
        $tables = $request->input('selected_tables', []);
        if (empty($tables) || !is_array($tables)) {
            return redirect()->back()->with('error', 'Please select at least one table to download.');
        }

        return $this->download(new Request([
            'tables'         => $tables,
            'structure_only' => $request->input('structure_only', false),
            'data_only'      => $request->input('data_only', false),
        ]));
    }

    /**
     * Quick Standalone Download (Direct URL access)
     */
    public function quickDownload(Request $request)
    {
        return $this->download($request);
    }

    /**
     * Memory-safe Streaming SQL Exporter
     */
    protected function streamSqlExport(array $tables, string $filename, bool $structureOnly = false, bool $dataOnly = false): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'application/sql; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($tables, $structureOnly, $dataOnly) {
            @ini_set('memory_limit', '-1');
            @set_time_limit(0);

            $pdo = DB::connection()->getPdo();
            $dbName = DB::connection()->getDatabaseName();
            $host = config('database.connections.mysql.host', '127.0.0.1');
            $now = date('Y-m-d H:i:s');
            $serverVersion = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);

            echo "-- =============================================================\n";
            echo "-- FSRQuiz Database Backup\n";
            echo "-- Host: {$host}\n";
            echo "-- Database: `{$dbName}`\n";
            echo "-- Export Date: {$now}\n";
            echo "-- MySQL Server Version: {$serverVersion}\n";
            echo "-- Total Tables Exported: " . count($tables) . "\n";
            echo "-- Tables: " . implode(', ', $tables) . "\n";
            echo "-- =============================================================\n\n";

            echo "SET FOREIGN_KEY_CHECKS=0;\n";
            echo "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
            echo "SET AUTOCOMMIT = 0;\n";
            echo "START TRANSACTION;\n";
            echo "SET time_zone = \"+00:00\";\n";
            echo "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
            echo "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
            echo "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
            echo "/*!40101 SET NAMES utf8mb4 */;\n\n";

            foreach ($tables as $table) {
                echo "-- -------------------------------------------------------------\n";
                echo "-- Structure & Data for Table `{$table}`\n";
                echo "-- -------------------------------------------------------------\n\n";

                // 1. Table Schema (CREATE TABLE)
                if (!$dataOnly) {
                    echo "DROP TABLE IF EXISTS `{$table}`;\n";
                    try {
                        $createRes = DB::select("SHOW CREATE TABLE `{$table}`");
                        if (!empty($createRes)) {
                            $createObj = (array) $createRes[0];
                            $createSql = $createObj['Create Table'] ?? ($createObj['Create View'] ?? null);
                            if ($createSql) {
                                echo $createSql . ";\n\n";
                            }
                        }
                    } catch (\Throwable $e) {
                        echo "-- Error fetching schema for table `{$table}`: " . $e->getMessage() . "\n\n";
                    }
                }

                // 2. Table Data (INSERT INTO)
                if (!$structureOnly) {
                    try {
                        $columnsResult = DB::select("SHOW COLUMNS FROM `{$table}`");
                        if (!empty($columnsResult)) {
                            $columnNames = array_map(fn($col) => (string) $col->Field, $columnsResult);
                            $escapedColumns = '`' . implode('`, `', $columnNames) . '`';

                            $stmt = $pdo->prepare("SELECT * FROM `{$table}`");
                            $stmt->execute();

                            $batchSize = 250;
                            $rowBuffer = [];
                            $isFirstInsert = true;

                            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                                if ($isFirstInsert) {
                                    echo "LOCK TABLES `{$table}` WRITE;\n";
                                    echo "/*!40000 ALTER TABLE `{$table}` DISABLE KEYS */;\n";
                                    $isFirstInsert = false;
                                }

                                $escapedValues = [];
                                foreach ($columnNames as $colName) {
                                    $val = $row[$colName] ?? null;
                                    if ($val === null) {
                                        $escapedValues[] = 'NULL';
                                    } elseif (is_int($val) || is_float($val)) {
                                        $escapedValues[] = (string) $val;
                                    } elseif (is_numeric($val) && !preg_match('/^0\d+/', (string) $val)) {
                                        $escapedValues[] = (string) $val;
                                    } else {
                                        $escapedValues[] = $pdo->quote((string) $val);
                                    }
                                }

                                $rowBuffer[] = '(' . implode(', ', $escapedValues) . ')';

                                if (count($rowBuffer) >= $batchSize) {
                                    echo "INSERT INTO `{$table}` ({$escapedColumns}) VALUES\n" . implode(",\n", $rowBuffer) . ";\n";
                                    $rowBuffer = [];
                                    if (ob_get_level() > 0) {
                                        @ob_flush();
                                    }
                                    @flush();
                                }
                            }

                            if (!empty($rowBuffer)) {
                                echo "INSERT INTO `{$table}` ({$escapedColumns}) VALUES\n" . implode(",\n", $rowBuffer) . ";\n";
                                $rowBuffer = [];
                                if (ob_get_level() > 0) {
                                    @ob_flush();
                                }
                                @flush();
                            }

                            if (!$isFirstInsert) {
                                echo "/*!40000 ALTER TABLE `{$table}` ENABLE KEYS */;\n";
                                echo "UNLOCK TABLES;\n\n";
                            }
                        }
                    } catch (\Throwable $e) {
                        echo "-- Error fetching data for table `{$table}`: " . $e->getMessage() . "\n\n";
                    }
                }
            }

            echo "COMMIT;\n";
            echo "SET FOREIGN_KEY_CHECKS=1;\n";
            echo "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
            echo "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n";
            echo "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";
            echo "-- =============================================================\n";
            echo "-- Dump completed successfully on " . date('Y-m-d H:i:s') . "\n";
            echo "-- =============================================================\n";

        }, 200, $headers);
    }

    /**
     * Memory-safe Streaming CSV Exporter for single table
     */
    protected function streamCsvExport(string $table, string $filename): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($table) {
            @ini_set('memory_limit', '-1');
            @set_time_limit(0);

            $output = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel compatibility
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            $columnsResult = DB::select("SHOW COLUMNS FROM `{$table}`");
            $columnNames = array_map(fn($col) => (string) $col->Field, $columnsResult);

            // Write CSV Header
            fputcsv($output, $columnNames);

            $pdo = DB::connection()->getPdo();
            $stmt = $pdo->prepare("SELECT * FROM `{$table}`");
            $stmt->execute();

            $counter = 0;
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $line = [];
                foreach ($columnNames as $col) {
                    $line[] = $row[$col] ?? '';
                }
                fputcsv($output, $line);
                $counter++;

                if ($counter % 500 === 0) {
                    if (ob_get_level() > 0) {
                        @ob_flush();
                    }
                    @flush();
                }
            }

            fclose($output);
        }, 200, $headers);
    }

    /**
     * Memory-safe Streaming JSON Exporter for single table
     */
    protected function streamJsonExport(string $table, string $filename): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'application/json; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($table) {
            @ini_set('memory_limit', '-1');
            @set_time_limit(0);

            echo "[\n";

            $pdo = DB::connection()->getPdo();
            $stmt = $pdo->prepare("SELECT * FROM `{$table}`");
            $stmt->execute();

            $isFirst = true;
            $counter = 0;

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                if (!$isFirst) {
                    echo ",\n";
                }
                $isFirst = false;

                echo "  " . json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $counter++;

                if ($counter % 300 === 0) {
                    if (ob_get_level() > 0) {
                        @ob_flush();
                    }
                    @flush();
                }
            }

            echo "\n]\n";
        }, 200, $headers);
    }

    /**
     * Helper to format bytes into readable KB, MB, GB
     */
    protected function formatBytes($bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
