<?php

namespace Servebolt\Optimizer\Admin\PerformanceChecks\Ajax;

use Servebolt\Optimizer\DatabaseOptimizer\DatabaseChecks;
use Servebolt\Optimizer\DatabaseOptimizer\DatabaseOptimizer;
use Servebolt\Optimizer\Admin\SharedAjaxMethods;
use function Servebolt\Optimizer\Helpers\deleteAllSettings;
use function Servebolt\Optimizer\Helpers\ajaxUserAllowed;

/**
 * Class OptimizeActions
 * @package Servebolt\Optimizer\Admin\PerformanceChecks\Ajax
 */
class OptimizeActions extends SharedAjaxMethods
{

    /**
     * OptimizeActions constructor.
     */
    public function __construct()
    {
        add_action('wp_ajax_servebolt_wreak_havoc', [$this, 'wreakHavocCallback']);
        add_action('wp_ajax_servebolt_clear_all_settings', [$this, 'clearAllSettingsCallback']);
        add_action('wp_ajax_servebolt_create_index', [$this, 'createIndexCallback']);
        add_action('wp_ajax_servebolt_optimize_db', [$this, 'optimizeDbCallback']);
        add_action('wp_ajax_servebolt_convert_table_to_innodb', [$this, 'convertTableToInnodbCallback']);
    }

    /**
     * Trigger database "de-optimization" (to undo our optimization for debugging purposes).
     */
    public function wreakHavocCallback(): void
    {
        $this->checkAjaxReferer();
        ajaxUserAllowed();
        $instance = DatabaseOptimizer::getInstance();
        $instance->deoptimizeIndexedTables();
        $instance->convertTablesToNonInnodb();
        wp_send_json_success();
    }

    /**
     * Clear all plugin settings.
     */
    public function clearAllSettingsCallback(): void
    {
        $this->checkAjaxReferer();
        ajaxUserAllowed();
        deleteAllSettings();
        wp_send_json_success();
    }

    /**
     * Add index to single table.
     */
    public function createIndexCallback(): void
    {
        $this->checkAjaxReferer();
        ajaxUserAllowed();

        $validatedData = $this->validateIndexCreationInput();
        if ( is_wp_error($validatedData) ) {
            wp_send_json_error(['message' => $validatedData->get_error_message()]);
        }

        $method = $validatedData['indexAdditionMethod'];
        $fullTableName = $validatedData['fullTableName'];
        $instance = DatabaseOptimizer::getInstance();

        if ($instance->tableHasIndexOnColumn($fullTableName, $validatedData['column'])) {
            wp_send_json_success([
                'message' => sprintf(__('Table "%s" already has index.', 'servebolt-wp'), $fullTableName)
            ]);
        } elseif (is_multisite() && $instance->$method($validatedData['blogId'])) {
            wp_send_json_success([
                'message' => sprintf(__('Added index to table "%s".', 'servebolt-wp'), $fullTableName)
            ]);
        } elseif (!is_multisite() && $instance->$method()) {
            wp_send_json_success([
                'message' => sprintf(__('Added index to table "%s".', 'servebolt-wp'), $fullTableName)
            ]);
        } else {
            wp_send_json_error([
                'message' => sprintf(__('Could not add index to table "%s".', 'servebolt-wp'), $fullTableName)
            ]);
        }
    }

    /**
     * Convert a single table to InnoDB.
     */
    public function convertTableToInnodbCallback(): void
    {
        $this->checkAjaxReferer();
        ajaxUserAllowed();

        $tableName = $this->validateInnodbConversionInput(false, false);
        if (is_wp_error($tableName)) {
            wp_send_json_error(['message' => $tableName->get_error_message()]);
        }

        $databaseOptimizer = DatabaseOptimizer::getInstance();
        $databaseChecks = DatabaseChecks::getInstance();

        if ($databaseOptimizer->table_is_innodb($tableName)) {
            wp_send_json_success([
                'message' => 'Table is already using InnoDB.',
            ]);
        } elseif (!$databaseChecks->tableValidForInnodbConversion($tableName)) {
            wp_send_json_error([
                'message' => 'Specified table is either invalid or unavailable.',
            ]);
        } elseif ($databaseOptimizer->convertTableToInnodb($tableName)) {
            wp_send_json_success([
                'message' => 'Table got converted to InnoDB.',
            ]);
        } else {
            wp_send_json_error([
                'message' => 'Unknown error.',
            ]);
        }
    }

    /**
     * Trigger database optimization.
     */
    public function optimizeDbCallback(): void
    {
        $this->checkAjaxReferer();
        ajaxUserAllowed();
        $instance = DatabaseOptimizer::getInstance();
        $result = $instance->optimizeDb();
        if ($result === false || $result['result'] === false) {
            wp_send_json_error();
        } else {
            wp_send_json_success($result );
        }
    }

    /**
     * Validating data when adding an index to a table.
     *
     * @return array|WP_Error
     */
    private function validateIndexCreationInput()
    {

        // Require table name to be specified
        $tableName = array_key_exists('table_name', $_POST) ? (string) sanitize_text_field($_POST['table_name']) : false;
        if (!$tableName) {
            return new WP_Error( 'table_name_not_specified', __('Table name not specified', 'servebolt-wp') );
        }

        if (is_multisite()) {

            // Require blog Id to be specified
            $blogId = array_key_exists('blog_id', $_POST) ? (int) sanitize_text_field($_POST['blog_id']) : false;
            if (!$blogId) {
                return new WP_Error( 'blog_id_not_specified', __('Blog ID not specified', 'servebolt-wp') );
            }

            // Require blog to exist
            $blog = get_blog_details(['blog_id' => $blogId]);
            if (!$blog) {
                return new WP_Error( 'invalid_blog_id', __('Invalid blog Id', 'servebolt-wp') );
            }

        }

        $databaseChecks = DatabaseChecks::getInstance();

        // Make sure we know which column to add index on in the table
        $column = $databaseChecks->getIndexColumnFromTable($tableName);
        if (!$column) {
            return new WP_Error( 'invalid_table_name', __('Invalid table name', 'servebolt-wp') );
        }

        $databaseOptimizer = DatabaseOptimizer::getInstance();

        // Make sure we found the table name to interact with
        $fullTableName = is_multisite() ? $databaseOptimizer->get_table_name_by_blog_id($blogId, $tableName) : $databaseOptimizer->get_table_name($tableName);
        if (!$fullTableName) {
            return new WP_Error( 'could_not_resolve_full_table_name', __('Could not resolve full table name', 'servebolt-wp') );
        }

        // Make sure we know which method to run to create the index
        $indexAdditionMethod = $this->getIndexCreationMethodByTableName($tableName);
        if (!$indexAdditionMethod) {
            return new WP_Error( 'could_not_resolve_index_creation_method_from_table_name', __('Could not resolve index creation method from table name', 'servebolt-wp') );
        }

        if (is_multisite()) {
            return compact('indexAdditionMethod', 'tableName', 'fullTableName', 'column', 'blogId');
        } else {
            return compact('indexAdditionMethod', 'tableName', 'fullTableName', 'column');
        }
    }

    /**
     * Identify which method we should use to optimize the given table type.
     *
     * @param $table_name
     *
     * @return null|string
     */
    private function getIndexCreationMethodByTableName($table_name): ?string
    {
        switch ($table_name) {
            case 'options':
                return 'add_options_autoload_index';
            case 'postmeta':
                return 'add_post_meta_index';
        }
        return null;
    }

    /**
     * Validate table name when converting a table to InnoDB.
     *
     * @return string|WP_Error
     */
    private function validateInnodbConversionInput()
    {
        // Require table name to be specified
        $tableName = array_key_exists('table_name', $_POST) ? (string) sanitize_text_field($_POST['table_name']) : false;
        if (!$tableName) {
            return new WP_Error( 'table_name_not_specified', __('Table name not specified', 'servebolt-wp') );
        }
        return $tableName;

    }
}
