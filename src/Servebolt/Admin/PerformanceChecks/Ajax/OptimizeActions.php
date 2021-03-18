<?php

namespace Servebolt\Optimizer\Admin\PerformanceChecks\Ajax;

use Servebolt\Optimizer\Admin\SharedAjaxMethods;

/**
 * Class OptimizeActions
 * @package Servebolt\Optimizer\Admin\PerformanceChecks\Ajax
 */
class OptimizeActions extends SharedAjaxMethods
{

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
        sb_ajax_user_allowed();
        sb_optimize_db()->deoptimizeIndexedTables();
        sb_optimize_db()->convertTablesToNonInnodb();
        wp_send_json_success();
    }

    /**
     * Clear all plugin settings.
     */
    public function clearAllSettingsCallback(): void
    {
        $this->checkAjaxReferer();
        sb_ajax_user_allowed();
        sb_delete_all_settings();
        wp_send_json_success();
    }

    /**
     * Add index to single table.
     */
    public function createIndexCallback()
    {
        $this->checkAjaxReferer();
        sb_ajax_user_allowed();

        $validated_data = $this->validateIndexCreationInput();
        if ( is_wp_error($validated_data) ) wp_send_json_error(['message' => $validated_data->get_error_message()]);

        $method = $validated_data['index_addition_method'];
        $full_table_name = $validated_data['full_table_name'];

        if ( sb_optimize_db()->table_has_index_on_column($full_table_name, $validated_data['column']) ) {
            wp_send_json_success([
                'message' => sprintf(sb__('Table "%s" already has index.'), $full_table_name)
            ]);
        } elseif ( is_multisite() && sb_optimize_db()->$method($validated_data['blog_id']) ) {
            wp_send_json_success([
                'message' => sprintf(sb__('Added index to table "%s".'), $full_table_name)
            ]);
        } elseif ( ! is_multisite() && sb_optimize_db()->$method() ) {
            wp_send_json_success([
                'message' => sprintf(sb__('Added index to table "%s".'), $full_table_name)
            ]);
        } else {
            wp_send_json_error([
                'message' => sprintf(sb__('Could not add index to table "%s".'), $full_table_name)
            ]);
        }

    }

    /**
     * Convert a single table to InnoDB.
     */
    public function convertTableToInnodbCallback()
    {
        $this->checkAjaxReferer();
        sb_ajax_user_allowed();

        $table_name = $this->validateInnodbConversionInput(false, false);
        if ( is_wp_error($table_name) ) wp_send_json_error(['message' => $table_name->get_error_message()]);

        if ( sb_optimize_db()->table_is_innodb($table_name) ) {
            wp_send_json_success([
                'message' => 'Table is already using InnoDB.',
            ]);
        } elseif ( ! ( sb_checks() )->tableValidForInnodbConversion($table_name) ) {
            wp_send_json_error([
                'message' => 'Specified table is either invalid or unavailable.',
            ]);
        } elseif ( sb_optimize_db()->convert_table_to_innodb($table_name) ) {
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
        sb_ajax_user_allowed();
        $result = sb_optimize_db()->optimizeDb();
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
        $table_name = array_key_exists('table_name', $_POST) ? (string) sanitize_text_field($_POST['table_name']) : false;
        if ( ! $table_name ) return new WP_Error( 'table_name_not_specified', sb__('Table name not specified') );

        if ( is_multisite() ) {

            // Require blog Id to be specified
            $blog_id = array_key_exists('blog_id', $_POST) ? (int) sanitize_text_field($_POST['blog_id']) : false;
            if ( ! $blog_id ) return new WP_Error( 'blog_id_not_specified', sb__('Blog ID not specified') );

            // Require blog to exist
            $blog = get_blog_details(['blog_id' => $blog_id]);
            if ( ! $blog ) return new WP_Error( 'invalid_blog_id', sb__('Invalid blog Id') );

        }

        // Make sure we know which column to add index on in the table
        $column = (sb_checks())->getIndexColumnFromTable($table_name);
        if ( ! $column ) return new WP_Error( 'invalid_table_name', sb__('Invalid table name') );

        // Make sure we found the table name to interact with
        $full_table_name = is_multisite() ? sb_optimize_db()->get_table_name_by_blog_id($blog_id, $table_name) : sb_optimize_db()->get_table_name($table_name);
        if ( ! $full_table_name ) return new WP_Error( 'could_not_resolve_full_table_name', sb__('Could not resolve full table name') );

        // Make sure we know which method to run to create the index
        $index_addition_method = $this->getIndexCreationMethodByTableName($table_name);
        if (!$index_addition_method) {
            return new WP_Error( 'could_not_resolve_index_creation_method_from_table_name', sb__('Could not resolve index creation method from table name') );
        }

        if (  is_multisite() ) {
            return compact('index_addition_method', 'table_name', 'full_table_name', 'column', 'blog_id');
        } else {
            return compact('index_addition_method', 'table_name', 'full_table_name', 'column');
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
                break;
            case 'postmeta':
                return 'add_post_meta_index';
                break;
        }
        return null;
    }

    /**
     * Validate table name when converting a table to InnoDB.
     *
     * @return array|WP_Error
     */
    private function validateInnodbConversionInput()
    {
        // Require table name to be specified
        $table_name = array_key_exists('table_name', $_POST) ? (string) sanitize_text_field($_POST['table_name']) : false;
        if (!$table_name) {
            return new WP_Error( 'table_name_not_specified', sb__('Table name not specified') );
        }
        return $table_name;

    }
}
