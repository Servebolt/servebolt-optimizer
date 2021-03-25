<?php

namespace Servebolt\Optimizer\SqlBuilder;

/**
 * Class SqlBuilder
 * @package Servebolt\Optimizer\Queue\QueueSystem
 */
class SqlBuilder
{
    private $query = '';
    private $prepareArguments = [];
    private $select = [];
    private $where = [];
    private $order;
    private $orderBy;
    private $tableName;
    private $firstWhereAdded = false;
    private $whereGroup = false;
    private $capturedWhereItems = [];

    public static function query(string $tableName)
    {
        return new self($tableName);
    }

    public function __construct(string $tableName)
    {
        $this->wpdb = $GLOBALS['wpdb'];
        $this->tableName = $this->wpdb->prefix . $tableName;
    }

    public function result()
    {
        $sql = $this->buildQuery();
        return $this->wpdb->get_results($sql);
    }

    public function count()
    {
        $this->result();
        return $this->wpdb->num_rows;
    }

    private function addToQuery($queryPart, bool $trim = true): void
    {
        if ($trim) {
            $queryIsEmpty = empty($this->query);
            $queryPart = ($queryIsEmpty ? '' : ' '). trim($queryPart);
        }
        $this->query .= $queryPart;
    }

    private function selectItems(): string
    {
        if (empty($this->select)) {
            return '*';
        }
        return implode(', ', $this->select);
    }

    public function buildQuery()
    {
        $this->query = '';
        $this->firstWhereAdded = false;
        $this->prepareArguments = [];

        $this->addToQuery("SELECT {$this->selectItems()}");
        $this->addToQuery("FROM `{$this->tableName}`");
        foreach ($this->where as $where) {
            $this->handleWhereWhileBuildingQuery($where);
        }
        if ($this->order) {
            $this->addToQuery("ORDER BY {$this->order}");
            if ($this->orderBy) {
                $this->addToQuery("{$this->orderBy}");
            }
        }

        return $this->wpdb->prepare($this->query, ...$this->prepareArguments);
    }

    private function addToPrepareArguments($value): void
    {
        $this->prepareArguments[] = $value;
    }

    private function addPrefixToQuery($prefix)
    {
        if (!$this->firstWhereAdded) {
            $this->addToQuery("WHERE");
            $this->firstWhereAdded = true;
        } else {
            $this->addToQuery($prefix);
        }
    }

    private function valueIsWhitelisted($key, $value, ?string $operator = null): bool
    {
        switch ($value) {
            case 'NULL':
                return true;
        }
        return false;
    }

    private function handleWhereWhileBuildingQuery($where)
    {
        $this->addPrefixToQuery($where['prefix']);
        switch ($where['type']) {
            case 'where':
                if ($this->valueIsWhitelisted($where['key'], $where['value'], $where['operator'])) {
                    $this->addToQuery("`{$where['key']}` {$where['operator']} {$where['value']}");
                } else {
                    $this->addToQuery("`{$where['key']}` {$where['operator']} %s");
                    $this->addToPrepareArguments($where['value']);
                }
                break;
            case 'whereGroup':
                $this->addToQuery('(');
                foreach ($where['items'] as $item) {
                    $this->handleWhereWhileBuildingQuery($item);
                }
                $this->addToQuery(')', false);
                break;
        }
    }

    private static function whereDefaults(string $key, string $value, string $operator = '=', ?string $prefix = null): array
    {
        return compact('key', 'value', 'operator', 'prefix');
    }

    public function select(string $select)
    {
        $this->select[] = $select;
    }

    public function andWhere($key, $value = null, $operator = null)
    {
        return $this->where($key, $value, $operator, 'AND');
    }

    private function defaultOperator(): string
    {
        return '=';
    }

    public function orWhere($key, $value = null, $operator = null)
    {

        return $this->where($key, $value, $operator, 'OR');
    }

    private function isWhereGroup(): bool
    {
        return $this->whereGroup === true;
    }

    private function startWhereGroup()
    {
        $this->whereGroup = true;
    }

    private function endWhereGroup()
    {
        $this->resetCapturedWhereItems();
        $this->whereGroup = false;
    }

    public function capturedWhereItems()
    {
        return $this->capturedWhereItems;
    }

    private function resetCapturedWhereItems()
    {
        $this->capturedWhereItems = [];
    }

    public function where($key, string $valueOrOperator = null, ?string $value = null, string $prefix = null): void
    {
        if (is_callable($key)) {
            $this->startWhereGroup();
            $key($this);
            $this->where[] = [
                'prefix' => $prefix,
                'type' => 'whereGroup',
                'items' => $this->capturedWhereItems(),
            ];
            $this->endWhereGroup();
        } else {

            if (!is_null($value)) {
                $operator = $valueOrOperator;
            } else {
                $value = $valueOrOperator;
                $operator = $this->defaultOperator();
            }

            $whereItem = array_merge(
                ['type' => 'where'],
                $this->whereDefaults($key, $value, $operator, $prefix)
            );

            if ($this->isWhereGroup()) {
                $this->capturedWhereItems[] = $whereItem;
            } else {
                $this->where[] = $whereItem;
            }
        }
    }

    public function order(string $order, ?string $orderBy = null): void
    {
        $this->order = $order;
        if ($orderBy) {
            $this->orderBy = $orderBy;
        }
    }
}
