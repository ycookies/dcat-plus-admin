<?php

namespace Dcat\Admin\Scaffold\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TableColumnInspector
{
    /**
     * 获取类似 SHOW FULL COLUMNS 的字段结构：Field/Type/Null/Default/Comment/Key
     * 支持 MySQL/MariaDB、SQLite、PostgreSQL、SQL Server。
     *
     * @param string $table
     * @return array<int, object>
     */
    public static function getShowLikeColumns(string $table, ?string $connectionName = null): array
    {
        if (! preg_match('/^[A-Za-z0-9_\.]+$/', $table)) {
            return [];
        }

        $connection = static::connection($connectionName);
        $driver = strtolower($connection->getDriverName());
        $prefixedTable = static::applyTablePrefix($connection, $table);

        if ($driver === 'mysql' || $driver === 'mariadb') {
            return $connection->select('SHOW FULL COLUMNS FROM '.static::quoteMySqlTable($prefixedTable));
        }

        if ($driver === 'sqlite') {
            $quotedTable = str_replace("'", "''", $prefixedTable);
            $columns = $connection->select("PRAGMA table_info('{$quotedTable}')");

            $uniqueColumns = [];
            $indexes = $connection->select("PRAGMA index_list('{$quotedTable}')");
            foreach ($indexes as $index) {
                if ((int) ($index->unique ?? 0) !== 1) {
                    continue;
                }

                $indexName = str_replace("'", "''", (string) ($index->name ?? ''));
                $indexColumns = $connection->select("PRAGMA index_info('{$indexName}')");
                foreach ($indexColumns as $idxCol) {
                    if (! empty($idxCol->name)) {
                        $uniqueColumns[(string) $idxCol->name] = true;
                    }
                }
            }

            return array_map(function ($col) use ($uniqueColumns) {
                return (object) [
                    'Field' => $col->name,
                    'Type' => strtolower((string) $col->type),
                    'Null' => ((int) ($col->notnull ?? 0) === 0) ? 'YES' : 'NO',
                    'Default' => $col->dflt_value,
                    'Comment' => '',
                    'Key' => ((int) ($col->pk ?? 0) === 1) ? 'PRI' : (isset($uniqueColumns[(string) $col->name]) ? 'UNI' : ''),
                ];
            }, $columns);
        }

        if ($driver === 'pgsql') {
            [$schema, $tableName] = static::parseSchemaTable($prefixedTable, Arr::get($connection->getConfig(), 'schema', 'public'));

            $sql = "
                SELECT
                    c.column_name AS \"Field\",
                    c.udt_name AS \"Type\",
                    c.is_nullable AS \"Null\",
                    c.column_default AS \"Default\",
                    COALESCE(pg_catalog.col_description(format('%I.%I', c.table_schema, c.table_name)::regclass, c.ordinal_position), '') AS \"Comment\",
                    CASE
                        WHEN tc.constraint_type = 'PRIMARY KEY' THEN 'PRI'
                        WHEN tc.constraint_type = 'UNIQUE' THEN 'UNI'
                        ELSE ''
                    END AS \"Key\"
                FROM information_schema.columns c
                LEFT JOIN information_schema.key_column_usage kcu
                    ON c.table_schema = kcu.table_schema
                    AND c.table_name = kcu.table_name
                    AND c.column_name = kcu.column_name
                LEFT JOIN information_schema.table_constraints tc
                    ON kcu.constraint_name = tc.constraint_name
                    AND kcu.table_schema = tc.table_schema
                    AND kcu.table_name = tc.table_name
                    AND tc.constraint_type IN ('PRIMARY KEY', 'UNIQUE')
                WHERE c.table_schema = ?
                    AND c.table_name = ?
                ORDER BY c.ordinal_position
            ";

            return $connection->select($sql, [$schema, $tableName]);
        }

        if ($driver === 'sqlsrv') {
            [$schema, $tableName] = static::parseSchemaTable($prefixedTable, Arr::get($connection->getConfig(), 'schema', 'dbo'));

            $sql = "
                SELECT
                    c.COLUMN_NAME AS [Field],
                    c.DATA_TYPE AS [Type],
                    c.IS_NULLABLE AS [Null],
                    c.COLUMN_DEFAULT AS [Default],
                    CAST(ep.value AS NVARCHAR(MAX)) AS [Comment],
                    CASE
                        WHEN tc.CONSTRAINT_TYPE = 'PRIMARY KEY' THEN 'PRI'
                        WHEN tc.CONSTRAINT_TYPE = 'UNIQUE' THEN 'UNI'
                        ELSE ''
                    END AS [Key]
                FROM INFORMATION_SCHEMA.COLUMNS c
                LEFT JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
                    ON c.TABLE_SCHEMA = kcu.TABLE_SCHEMA
                    AND c.TABLE_NAME = kcu.TABLE_NAME
                    AND c.COLUMN_NAME = kcu.COLUMN_NAME
                LEFT JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
                    ON kcu.CONSTRAINT_NAME = tc.CONSTRAINT_NAME
                    AND kcu.TABLE_SCHEMA = tc.TABLE_SCHEMA
                    AND tc.CONSTRAINT_TYPE IN ('PRIMARY KEY', 'UNIQUE')
                LEFT JOIN sys.extended_properties ep
                    ON ep.major_id = OBJECT_ID(c.TABLE_SCHEMA + '.' + c.TABLE_NAME)
                    AND ep.minor_id = c.ORDINAL_POSITION
                    AND ep.name = 'MS_Description'
                WHERE c.TABLE_SCHEMA = ?
                    AND c.TABLE_NAME = ?
                ORDER BY c.ORDINAL_POSITION
            ";

            return $connection->select($sql, [$schema, $tableName]);
        }

        return [];
    }

    /**
     * Get table names for the given connection.
     *
     * @param string|null $connectionName
     * @return array<int, string>
     */
    public static function getTableNames(?string $connectionName = null): array
    {
        $connection = static::connection($connectionName);
        $driver = strtolower($connection->getDriverName());
        $prefix = $connection->getTablePrefix();
        $tables = [];

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $database = $connection->getDatabaseName();
            $rows = $connection->select(
                'SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = ? AND table_type = ? ORDER BY TABLE_NAME',
                [$database, 'BASE TABLE']
            );

            foreach ($rows as $row) {
                $tables[] = static::stripTablePrefix((string) $row->TABLE_NAME, $prefix);
            }
        } elseif ($driver === 'sqlite') {
            $rows = $connection->select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name");

            foreach ($rows as $row) {
                $tables[] = static::stripTablePrefix((string) $row->name, $prefix);
            }
        } elseif ($driver === 'pgsql') {
            $schema = Arr::get($connection->getConfig(), 'schema', 'public');
            $rows = $connection->select(
                "SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_type = 'BASE TABLE' ORDER BY table_name",
                [$schema]
            );

            foreach ($rows as $row) {
                $tables[] = static::stripTablePrefix((string) $row->table_name, $prefix);
            }
        } elseif ($driver === 'sqlsrv') {
            $schema = Arr::get($connection->getConfig(), 'schema', 'dbo');
            $rows = $connection->select(
                "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME",
                [$schema]
            );

            foreach ($rows as $row) {
                $tables[] = static::stripTablePrefix((string) $row->TABLE_NAME, $prefix);
            }
        }

        return array_values(array_unique($tables));
    }

    /**
     * 获取标准化后的字段结构，便于业务代码复用.
     *
     * @param string $table
     * @return array<string, array<string, mixed>>
     */
    public static function getNormalizedColumns(string $table, ?string $connectionName = null): array
    {
        $columns = static::getShowLikeColumns($table, $connectionName);
        $result = [];

        foreach ($columns as $column) {
            $column = (array) $column;
            $fieldName = Arr::get($column, 'Field');
            if (! $fieldName) {
                continue;
            }

            $type = strtolower((string) Arr::get($column, 'Type', ''));
            if (Str::contains($type, 'unsigned') && ! Str::contains($type, '@unsigned')) {
                $type .= '@unsigned';
            }

            $key = (string) Arr::get($column, 'Key', '');

            $result[$fieldName] = [
                'type' => $type,
                'comment' => Arr::get($column, 'Comment', ''),
                'nullable' => Arr::get($column, 'Null') === 'YES',
                'default' => Arr::get($column, 'Default'),
                'key' => $key,
                'id' => $key === 'PRI',
            ];
        }

        return $result;
    }

    /**
     * Get database connection.
     *
     * @param string|null $connectionName
     * @return \Illuminate\Database\ConnectionInterface
     */
    protected static function connection(?string $connectionName = null)
    {
        return $connectionName ? DB::connection($connectionName) : DB::connection();
    }

    /**
     * Apply Laravel table prefix for raw schema queries.
     *
     * @param mixed $connection
     * @param string $table
     * @return string
     */
    protected static function applyTablePrefix($connection, string $table): string
    {
        $prefix = $connection->getTablePrefix();

        if (! $prefix || Str::contains($table, '.') || Str::startsWith($table, $prefix)) {
            return $table;
        }

        return $prefix.$table;
    }

    /**
     * Remove Laravel table prefix from discovered table names.
     *
     * @param string $table
     * @param string $prefix
     * @return string
     */
    protected static function stripTablePrefix(string $table, string $prefix): string
    {
        if ($prefix && Str::startsWith($table, $prefix)) {
            return Str::replaceFirst($prefix, '', $table);
        }

        return $table;
    }

    /**
     * Quote a MySQL table name, including optional database/table notation.
     *
     * @param string $table
     * @return string
     */
    protected static function quoteMySqlTable(string $table): string
    {
        return implode('.', array_map(function ($part) {
            return '`'.str_replace('`', '``', $part).'`';
        }, explode('.', $table)));
    }

    /**
     * Parse schema-qualified table names.
     *
     * @param string $table
     * @param string $defaultSchema
     * @return array{0: string, 1: string}
     */
    protected static function parseSchemaTable(string $table, string $defaultSchema): array
    {
        if (Str::contains($table, '.')) {
            [$schema, $table] = explode('.', $table, 2);

            return [$schema, $table];
        }

        return [$defaultSchema, $table];
    }
}
