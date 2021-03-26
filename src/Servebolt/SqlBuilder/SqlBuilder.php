<?php

namespace Servebolt\Optimizer\SqlBuilder;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class SqlBuilder
 * @package Servebolt\Optimizer\Queue\QueueSystem
 */
class SqlBuilder
{

    /**
     * @var string Table name.
     */
    private $tableName;

    /**
     * @var bool Whether we have added the "WHERE"-string to the query string already.
     */
    private $firstWhereAdded = false;

    /**
     * @var bool Whether this is a count query.
     */
    private $selectCount = false;

    /**
     * @var string Query string.
     */
    private $query = '';

    /**
     * @var array Array of arguments that will be populate into the query string.
     */
    private $prepareArguments = [];

    /**
     * @var array Items for query-part of query string.
     */
    private $select = [];

    /**
     * @var array Items for where-part of query string.
     */
    private $where = [];

    /**
     * @var array Order-parameter.
     */
    private $order;

    /**
     * @var array Order by-parameter.
     */
    private $orderBy;

    /**
     * @var array Limit parameter
     */
    private $limit = [];

    /**
     * @var string What the query should start with.
     */
    private $statementType = 'SELECT';

    /**
     * SqlBuilder constructor.
     * @param string|null $tableName
     */
    public function __construct(?string $tableName = null)
    {
        $this->wpdb = $GLOBALS['wpdb'];
        if ($tableName) {
            $this->from($tableName);
        }
    }

    /**
     * Instance factory.
     *
     * @param string|null $tableName
     * @return SqlBuilder
     */
    public static function query(?string $tableName = null)
    {
        return new self($tableName);
    }

    /**
     * @param string $tableName
     * @return SqlBuilder
     */
    public function from(string $tableName): self
    {
        if (substr($tableName, 0, strlen($this->wpdb->prefix)) !== $this->wpdb->prefix) {
            $this->tableName = $this->wpdb->prefix . $tableName;
        } else {
            $this->tableName = $tableName;
        }
        return $this;
    }

    /**
     * @return mixed|null
     */
    public function first()
    {
        $result = $this->result();
        if (is_array($result)) {
            return current($result);
        }
        return null;
    }

    /**
     * @return mixed
     */
    public function run()
    {
        $sql = $this->buildQuery();
        $this->wpdb->query($sql);
    }

    /**
     * @return mixed
     */
    public function getVar()
    {
        $sql = $this->buildQuery();
        return $this->wpdb->get_var($sql);
    }

    /**
     * @return mixed
     */
    public function result()
    {
        $sql = $this->buildQuery();
        if ($this->selectCount) {
            return $this->wpdb->get_var($sql);
        }
        return $this->getResults($sql);
    }

    /**
     * @param string $sql
     * @return mixed
     */
    protected function getResults(string $sql)
    {
        return $this->wpdb->get_results($sql);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        $this->result();
        return $this->wpdb->num_rows;
    }

    /**
     * @param string $queryPart
     * @param bool $trim
     */
    private function addToQuery(string $queryPart, bool $trim = true): void
    {
        if ($trim) {
            $queryIsEmpty = empty($this->query);
            $queryPart = ($queryIsEmpty ? '' : ' ') . trim($queryPart);
        }
        $this->query .= $queryPart;
    }

    /**
     * @return string
     */
    private function selectItems(): string
    {
        if (empty($this->select)) {
            return '*';
        }
        return implode(', ', $this->select);
    }

    /**
     * @return string
     */
    public function buildQuery(): string
    {
        $this->resetQueryBuild();

        switch ($this->statementType) {
            case 'SELECT':
                $this->addToQuery("SELECT {$this->selectItems()}");
                $this->addToQuery("FROM `{$this->tableName}`");
                // TODO: Add joins
                $this->addWhereItemsToQuery();
                // TODO: Add having
                $this->addOrderParameterToQuery();
                $this->addLimitParameter();
                break;
            case 'DELETE':
                $this->addToQuery("DELETE FROM `{$this->tableName}`");
                // TODO: Add joins
                $this->addWhereItemsToQuery();
                $this->addOrderParameterToQuery();
                $this->addLimitParameter();
                break;

        }

        return $this->prepareQuery();
    }

    private function addLimitParameter()
    {
        if (!empty($this->limit)) {
            $limitString = implode(', ', $this->limit);
            $this->addToQuery("LIMIT {$limitString}");
        }
    }

    private function resetQueryBuild(): void
    {
        $this->query = '';
        $this->firstWhereAdded = false;
        $this->prepareArguments = [];
    }

    private function prepareQuery(): string
    {
        if (empty($this->prepareArguments)) {
            return $this->query; // No external arguments
        }
        return $this->wpdb->prepare($this->query, ...$this->prepareArguments);
    }

    private function addOrderParameterToQuery(): void
    {
        if ($this->order) {
            $this->addToQuery("ORDER BY {$this->order}");
            if ($this->orderBy) {
                $this->addToQuery($this->orderBy);
            }
        }
    }

    private function addToPrepareArguments($value): void
    {
        $this->prepareArguments[] = $value;
    }

    private function addPrefixToQuery($prefix): void
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

    private function addWhereItemsToQuery(): void
    {
        $skipPrefixOnNext = false;
        foreach ($this->where as $where) {
            switch ($where['type']) {
                case 'where':

                    if ($skipPrefixOnNext === false) {
                        $this->addPrefixToQuery($where['prefix']);
                    }

                    if ($this->valueIsWhitelisted($where['key'], $where['value'], $where['operator'])) {
                        $this->addToQuery("`{$where['key']}` {$where['operator']} {$where['value']}", !$skipPrefixOnNext);
                    } else {
                        $this->addToQuery("`{$where['key']}` {$where['operator']} %s", !$skipPrefixOnNext);
                        $this->addToPrepareArguments($where['value']);
                    }
                    if ($skipPrefixOnNext === true) {
                        $skipPrefixOnNext = false;
                    }
                    break;
                case 'whereGroupStart':
                    $this->addPrefixToQuery($where['prefix']);
                    $this->addToQuery('(');
                    $skipPrefixOnNext = true;
                    break;
                case 'whereGroupEnd':
                    $this->addToQuery(')', false);
                    break;
            }
        }
    }

    private static function whereDefaults(string $key, string $value, string $operator = '=', ?string $prefix = null): array
    {
        return array_merge(
            ['type' => 'where'],
            compact('key', 'value', 'operator', 'prefix'),
        );
    }

    public function delete()
    {
        $this->statementType = 'DELETE';
    }

    public function selectCount()
    {
        $this->statementType = 'SELECT';
        $this->select('COUNT(*)');
        $this->selectCount = true;
        return $this;
    }

    public function select(string $select)
    {
        $this->statementType = 'SELECT';
        $this->select[] = $select;
        return $this;
    }

    private function defaultWhereOperator(): string
    {
        return '=';
    }

    private function defaultWherePrefix(): string
    {
        return 'AND';
    }

    public function andWhere($key, ?string $value = null, ?string $operator = null)
    {
        $this->where($key, $value, $operator, 'AND');
        return $this;
    }

    public function orWhere($key, ?string $value = null, ?string $operator = null)
    {
        $this->where($key, $value, $operator, 'OR');
        return $this;
    }

    public function where($key, ?string $valueOrOperator = null, ?string $value = null, ?string $prefix = null)
    {
        $isWhereGroup = is_callable($key);
        $closure = $key;

        if (is_null($prefix)) {
            $prefix = $this->defaultWherePrefix();
        }

        if ($isWhereGroup) {
            $this->where[] = [
                'prefix' => $prefix,
                'type' => 'whereGroupStart',
            ];
            $closure($this); // Where group callable closure
            $this->where[] = [
                'type' => 'whereGroupEnd',
            ];
        } else {
            if (!is_null($value)) {
                $operator = $valueOrOperator;
            } else {
                $value = $valueOrOperator;
                $operator = $this->defaultWhereOperator();
            }
            /*
            if (is_null($value)) {
                var_dump($key);
                var_dump($value);
                var_dump($operator);
                var_dump($prefix);
                die;
            }
            */
            $this->where[] = $this->whereDefaults($key, $value, $operator, $prefix);
        }
        return $this;
    }

    public function removeOrder()
    {
        $this->order = null;
        $this->orderBy = null;
    }

    public function removeLimit()
    {
        $this->limit = [];
    }

    public function limit(int $rowCount, ?int $offset = null)
    {
        $this->limit = [];
        if ($offset) {
            $this->limit[] = $offset;
        }
        $this->limit[] = $rowCount;
        return $this;
    }

    public function orderBy(string $order, ?string $orderBy = null)
    {
        $this->order($order, $orderBy);
        return $this;
    }

    public function order(string $order, ?string $orderBy = null)
    {
        $this->order = $order;
        if ($orderBy) {
            $this->orderBy = $orderBy;
        }
        return $this;
    }
}
