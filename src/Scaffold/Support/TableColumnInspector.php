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
    public static function getShowLikeColumns(string $table): array
    {
        if (! preg_match('/^[A-Za-z0-9_\.]+$/', $table)) {
            return [];
        }

        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            return $connection->select('SHOW FULL COLUMNS FROM `' . str_replace('`', '``', $table) . '`');
        }

        if ($driver === 'sqlite') {
            $quotedTable = str_replace("'", "''", $table);
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
                WHERE c.table_schema = current_schema()
                    AND c.table_name = ?
                ORDER BY c.ordinal_position
            ";

            return $connection->select($sql, [$table]);
        }

        if ($driver === 'sqlsrv') {
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
                WHERE c.TABLE_NAME = ?
                ORDER BY c.ORDINAL_POSITION
            ";

            return $connection->select($sql, [$table]);
        }

        return [];
    }

    /**
     * 获取标准化后的字段结构，便于业务代码复用.
     *
     * @param string $table
     * @return array<string, array<string, mixed>>
     */
    public static function getNormalizedColumns(string $table): array
    {
        $columns = static::getShowLikeColumns($table);
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
}
