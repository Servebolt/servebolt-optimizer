<?php

namespace Servebolt\Optimizer\SqlBuilder;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class WpSqlBuilder
 * @package Servebolt\Optimizer\Queue\QueueSystem
 */
class WpSqlBuilder extends SqlBuilder
{

    /**
     * @var wpdb WPDB-instance.
     */
    protected $wpdb;

    /**
     * WpSqlBuilder constructor.
     * @param string|null $tableName
     */
    public function __construct(?string $tableName = null)
    {
        $this->wpdb = $GLOBALS['wpdb'];
        parent::__construct($tableName);
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
    public function from(string $tableName)
    {
        if (substr($tableName, 0, strlen($this->wpdb->prefix)) !== $this->wpdb->prefix) {
            $tableName = $this->wpdb->prefix . $tableName;
        } else {
            $tableName = $tableName;
        }
        return parent::from($tableName);
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
     * @return int
     */
    public function count(): int
    {
        $this->result();
        return $this->wpdb->num_rows;
    }

    protected function prepareQuery(): string
    {
        if (empty($this->prepareArguments)) {
            return $this->query; // No external arguments
        }
        return $this->wpdb->prepare($this->query, ...$this->prepareArguments);
    }

}
