<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Servebolt_Performance_Checks
 *
 * This class display the optimization options and handles execution of optimizations.
 */
class Servebolt_Performance_Checks {

	/**
	* @var null Singleton instance.
	*/
	private static $instance = null;

	/**
	* Singleton instantiation.
	*
	* @return Servebolt_Performance_Checks|null
	*/
	public static function get_instance() {
		if ( self::$instance == null ) {
			self::$instance = new Servebolt_Performance_Checks;
		}
		return self::$instance;
	}

	/**
	* Servebolt_Performance_Checks constructor.
	*/
	private function __construct() {}

	/**
	* Initialize events.
	*/
	public function init() {
		$this->add_ajax_handling();
	}

	/**
	* Add AJAX handling.
	*/
	private function add_ajax_handling() {
		add_action('wp_ajax_servebolt_wreak_havoc', [$this, 'wreak_havoc_callback']);
		add_action('wp_ajax_servebolt_clear_all_settings', [$this, 'clear_all_settings_callback']);
		add_action('wp_ajax_servebolt_create_index', [$this, 'create_index_callback']);
		add_action('wp_ajax_servebolt_optimize_db', [$this, 'optimize_db_callback']);
		add_action('wp_ajax_servebolt_convert_table_to_innodb', [$this, 'convert_table_to_innodb_callback']);
	}

	/**
	 * Clear all plugin settings.
	 */
	public function clear_all_settings_callback() {
		check_ajax_referer(sb_get_ajax_nonce_key(), 'security');
		sb_clear_all_settings();
	}

	/**
	 * Trigger database "de-optimization" (to undo our optimization for debugging purposes).
	 */
	public function wreak_havoc_callback() {
		check_ajax_referer(sb_get_ajax_nonce_key(), 'security');
		sb_optimize_db()->deoptimize_indexed_tables();
		sb_optimize_db()->convert_tables_to_non_innodb();
	}

	/**
	 * Validating data when adding an index to a table.
	 *
	 * @return array|WP_Error
	 */
	private function validate_index_creation_input() {

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
		$column = (sb_checks())->get_index_column_from_table($table_name);
		if ( ! $column ) return new WP_Error( 'invalid_table_name', sb__('Invalid table name') );

		// Make sure we found the table name to interact with
		$full_table_name = is_multisite() ? sb_optimize_db()->get_table_name_by_blog_id($blog_id, $table_name) : sb_optimize_db()->get_table_name($table_name);
		if ( ! $full_table_name ) return new WP_Error( 'could_not_resolve_full_table_name', sb__('Could not resolve full table name') );

		// Make sure we know which method to run to create the index
		$index_addition_method = $this->get_index_creation_method_by_table_name($table_name);
		if ( ! $index_addition_method ) return new WP_Error( 'could_not_resolve_index_creation_method_from_table_name', sb__('Could not resolve index creation method from table name') );

		if (  is_multisite() ) {
			return compact('index_addition_method', 'table_name', 'full_table_name', 'column', 'blog_id');
		} else {
			return compact('index_addition_method', 'table_name', 'full_table_name', 'column');
		}

	}

	/**
	 * Add index to single table.
	 */
	public function create_index_callback() {
		check_ajax_referer(sb_get_ajax_nonce_key(), 'security');

		$validated_data = $this->validate_index_creation_input();
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
	 * Validate table name when converting a table to InnoDB.
	 *
	 * @return array|WP_Error
	 */
	private function validate_innodb_conversion_input() {

		// Require table name to be specified
		$table_name = array_key_exists('table_name', $_POST) ? (string) sanitize_text_field($_POST['table_name']) : false;
		if ( ! $table_name ) return new WP_Error( 'table_name_not_specified', sb__('Table name not specified') );

		return $table_name;

	}

	/**
	 * Convert a single table to InnoDB.
	 */
	public function convert_table_to_innodb_callback() {
		check_ajax_referer(sb_get_ajax_nonce_key(), 'security');

		$table_name = $this->validate_innodb_conversion_input(false, false);
		if ( is_wp_error($table_name) ) wp_send_json_error(['message' => $table_name->get_error_message()]);

		if ( sb_optimize_db()->table_is_innodb($table_name) ) {
			wp_send_json_success([
				'message' => 'Table is already using InnoDB.',
			]);
		} elseif ( ! ( sb_checks() )->table_valid_for_innodb_conversion($table_name) ) {
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
	 * Identify which method we should use to optimize the given table type.
	 *
	 * @param $table_name
	 *
	 * @return bool|string
	 */
	private function get_index_creation_method_by_table_name($table_name) {
		switch ($table_name) {
			case 'options':
				return 'add_options_autoload_index';
				break;
			case 'postmeta':
				return 'add_post_meta_index';
				break;
		}
		return false;
	}

	/**
	 * Trigger database optimization.
	 */
	public function optimize_db_callback() {
		check_ajax_referer(sb_get_ajax_nonce_key(), 'security');
		$result = sb_optimize_db()->optimize_db();
		if ( $result === false || $result['result'] === false ) {
			wp_send_json_error();
		} else {
			wp_send_json_success($result );
		}
	}

	/**
	 * Check if any of the tables in the array needs indexing.
	 *
	 * @param $tables
	 *
	 * @return bool
	 */
	private function tables_need_index($tables) {
		foreach ( $tables as $table ) {
			if ( ! $table['has_index'] ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Display performance checks view.
	 */
	public function view() {
		$checks_class = sb_checks();
		$tables_to_index = $checks_class->tables_to_have_index();
		sb_view('admin/views/performance-checks', [
			'index_fix_available' => $this->tables_need_index($tables_to_index),
			'tables'              => $tables_to_index,
			'myisam_tables'       => $checks_class->get_myisam_tables(),
			'wp_cron_disabled'    => $checks_class->wp_cron_disabled(),
		]);
	}

}
Servebolt_Performance_Checks::get_instance()->init();
