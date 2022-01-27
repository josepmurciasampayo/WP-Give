<?php

namespace Give\Framework\QueryBuilder\Concerns;

use Give\Framework\QueryBuilder\JoinQueryBuilder;
use Give\Framework\QueryBuilder\Models\MetaTable;

/**
 * @unreleased
 */
trait MetaQuery
{

    /**
     * @var MetaTable[]
     */
    private $metaTablesConfigs = [];

    /**
     * @var string
     */
    private $defaultMetaKeyColumn = 'meta_key';

    /**
     * @var string
     */
    private $defaultMetaValueColumn = 'meta_value';

    /**
     * @param  string  $table
     * @param  string  $metaKeyColumn
     * @param  string  $metaValueColumn
     *
     * @return $this
     */
    public function configureMetaTable($table, $metaKeyColumn, $metaValueColumn)
    {
        $this->metaTablesConfigs[] = new MetaTable(
            $table,
            $metaKeyColumn,
            $metaValueColumn
        );

        return $this;
    }

    /**
     * @param $table
     *
     * @return MetaTable
     */
    protected function getMetaTable($table)
    {
        foreach ($this->metaTablesConfigs as $metaTable) {
            if ($metaTable->tableName === $table) {
                return $metaTable;
            }
        }

        return new MetaTable(
            $table,
            $this->defaultMetaKeyColumn,
            $this->defaultMetaValueColumn
        );
    }

    /**
     * Select meta columns
     *
     * @param  string  $table
     * @param  string  $foreignKey
     * @param  string  $primaryKey
     * @param  array  $columns
     *
     * @return $this
     */
    public function attachMeta($table, $foreignKey, $primaryKey, ...$columns)
    {
        $metaTable = $this->getMetaTable($table);

        foreach ($columns as $i => $entry) {
            if (is_array($entry)) {
                list ($column, $columnAlias) = $entry;
            } else {
                $column = $entry;
                $columnAlias = null;
            }

            // Set dynamic alias
            $tableAlias = sprintf('%s_%s_%d', $table, 'attach_meta', $i);

            $this->join(
                function (JoinQueryBuilder $builder) use ($table, $foreignKey, $primaryKey, $tableAlias, $column, $metaTable) {
                    $builder
                        ->leftJoin($table, $tableAlias)
                        ->on($foreignKey, "{$tableAlias}.{$primaryKey}")
                        ->and("{$tableAlias}.{$metaTable->keyColumnName}", $column, true);
                }
            );

            $this->select(["{$tableAlias}.{$metaTable->valueColumnName}", $columnAlias ? : $column]);
        }

        return $this;
    }
}
