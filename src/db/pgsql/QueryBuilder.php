<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\db\pgsql;

use Craft;
use craft\app\db\Connection;
use yii\db\Expression;

/**
 * @inheritdoc
 *
 * @property Connection $db Connection the DB connection that this command is associated with.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class QueryBuilder extends \yii\db\pgsql\QueryBuilder
{
    /**
     * Builds a SQL statement for dropping a DB table if it exists.
     *
     * @param string $table The table to be dropped. The name will be properly quoted by the method.
     *
     * @return string The SQL statement for dropping a DB table.
     */
    public function dropTableIfExists($table)
    {
        return 'DROP TABLE IF EXISTS '.$this->db->quoteTableName($table);
    }

    /**
     * Builds a SQL statement for inserting some given data into a table, or updating an existing row
     * in the event of a key constraint violation.
     *
     * @param string $table               The table that the row will be inserted into, or updated.
     * @param array  $keyColumns          The key-constrained column data (name => value) to be inserted into the table
     *                                    in the event that a new row is getting created
     * @param array  $updateColumns       The non-key-constrained column data (name => value) to be inserted into the table
     *                                    or updated in the existing row.
     * @param array  $params              The binding parameters that will be generated by this method.
     *                                    They should be bound to the DB command later.
     *
     * @return string The SQL statement for inserting or updating data in a table.
     */
    public function upsert($table, $keyColumns, $updateColumns, &$params)
    {
        $schema = $this->db->getSchema();

        if (($tableSchema = $schema->getTableSchema($table)) !== null) {
            $columnSchemas = $tableSchema->columns;
        } else {
            $columnSchemas = [];
        }

        $columns = array_merge($keyColumns, $updateColumns);
        $names = [];
        $placeholders = [];
        $updates = [];

        foreach ($columns as $name => $value) {
            $qName = $schema->quoteColumnName($name);
            $names[] = $qName;

            if ($value instanceof Expression) {
                $placeholder = $value->expression;

                foreach ($value->params as $n => $v) {
                    $params[$n] = $v;
                }
            } else {
                $phName = static::PARAM_PREFIX.count($params);
                $placeholder = $phName;
                $params[$phName] = !is_array($value) && isset($columnSchemas[$name]) ? $columnSchemas[$name]->dbTypecast($value) : $value;
            }

            $placeholders[] = $placeholder;

            // Was this an update column?
            if (isset($updateColumns[$name])) {
                $updates[] = "$qName = $placeholder";
            }
        }

        $primaryKeys = $schema->getTableSchema($table)->primaryKey;

        if (!is_array($primaryKeys)) {
            $primaryKeys = [$primaryKeys];
        }

        $sql = 'INSERT INTO '.$schema->quoteTableName($table).
        ' ('.implode(', ', $names).') VALUES ('.implode(', ', $placeholders).')'.
        ' ON CONFLICT ("'.implode('", "', $primaryKeys).'") DO UPDATE SET ';

        foreach ($updates as $counter => $update) {
            if ($counter > 0) {
                $sql .= ', ';
            }

            $sql .= $update;
        }

        return $sql;
    }

    /**
     * Builds a SQL statement for replacing some text with other text in a given table column.
     *
     * @param string       $table     The table to be updated.
     * @param string       $column    The column to be searched.
     * @param string       $find      The text to be searched for.
     * @param string       $replace   The replacement text.
     * @param array|string $condition The condition that will be put in the WHERE part. Please
          *                           refer to [[Query::where()]] on how to specify condition.
     * @param array        $params    The binding parameters that will be generated by this method.
     *                                They should be bound to the DB command later.
     *
     * @return string The SQL statement for replacing some text in a given table.
     */
    public function replace($table, $column, $find, $replace, $condition, &$params)
    {
        $column = $this->db->quoteColumnName($column);

        $findPhName = static::PARAM_PREFIX.count($params);
        $params[$findPhName] = $find;

        $replacePhName = static::PARAM_PREFIX.count($params);
        $params[$replacePhName] = $replace;

        $sql = 'UPDATE '.$table.
            " SET $column = REPLACE($column, $findPhName, $replacePhName)";

        $where = $this->buildWhere($condition, $params);

        return $where === '' ? $sql : $sql . ' ' . $where;
    }

    /**
     * Builds the SQL expression used to return a DB result in a fixed order.
     * http://stackoverflow.com/a/1310188/684
     *
     * @param string $column The column name that contains the values.
     * @param array  $values The column values, in the order in which the rows should be returned in.
     *
     * @return string The SQL expression.
     */
    public function fixedOrder($column, $values)
    {
        $schema = $this->db->getSchema();

        $sql = 'CASE';

        foreach ($values as $key => $value) {
            $sql .= ' WHEN '.$schema->quoteColumnName($column).'='.$schema->quoteValue($value).' THEN '.$schema->quoteValue($key);
        }

        $sql .= ' ELSE '.$schema->quoteValue($key + 1).' END';

        return $sql;
    }
}
