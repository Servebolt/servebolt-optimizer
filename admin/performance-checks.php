<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
* Class Servebolt_Performance_Checks
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
	function init() {
		$this->add_ajax_handling();
	}

	/**
	* Add AJAX handling.
	*/
	private function add_ajax_handling() {
		add_action('wp_ajax_servebolt_wreak_havoc', [$this, 'wreak_havoc_callback']);
		add_action('wp_ajax_servebolt_create_index', [$this, 'create_index_callback']);
		add_action('wp_ajax_servebolt_optimize_db', [$this, 'optimize_db_callback']);
		add_action('wp_ajax_servebolt_convert_table_to_innodb', [$this, 'convert_table_to_innodb_callback']);
	}

	public function wreak_havoc_callback() {
		check_ajax_referer(sb_get_ajax_nonce_key(), 'security');
		$this->remove_all_indexes();
	}

	/**
	 * Remove all indexes (created by us).
	 */
	private function remove_all_indexes() {
		$sb_optimizer_db = Servebolt_Optimize_DB::get_instance();
		$sb_optimizer_db->deoptimize_indexed_tables();
		$sb_optimizer_db->convert_tables_to_non_innodb();
	}

	/**
	 * Add index to single table.
	 */
	public function create_index_callback() {
		check_ajax_referer(sb_get_ajax_nonce_key(), 'security');

		$table_name = sanitize_text_field($_POST['table_name']);
		$blog_id    = (int) sanitize_text_field($_POST['blog_id']);
		$blog       = get_blog_details(['blog_id' => $blog_id]);

		if ( ! $blog ) {
			wp_send_json_error(['message' => sb__('Invalid blog Id.')]);
			return;
		}

		$column = (sb_checks())->get_index_column_from_table($table_name);
		if ( ! $column ) {
			wp_send_json_error(['message' => sb__('Invalid table name.')]);
			return;
		}

		$sb_optimizer_db       = Servebolt_Optimize_DB::get_instance();
		$full_table_name       = $sb_optimizer_db->get_table_name_by_blog_id($blog_id, $table_name);
		$index_addition_method = $table_name == 'options' ? 'add_options_autoload_index' : 'add_post_meta_index';

		if ( $sb_optimizer_db->table_has_index_on_column($full_table_name, $column) ) {
			wp_send_json_success(['message' => sb__('Table already has index.')]);
		} elseif ( $sb_optimizer_db->$index_addition_method($blog_id) ) {
			wp_send_json_success(['message' => sb__(sprintf('Added index to %s table.', $table_name))]);
		} else {
			wp_send_json_error(['message' => sb__(sprintf('Could not add index to %s table.', $table_name))]);
		}

	}

	/**
	 * Convert a single table to InnoDB.
	 */
	public function convert_table_to_innodb_callback() {
		check_ajax_referer(sb_get_ajax_nonce_key(), 'security');

		$table_name      = sanitize_text_field($_POST['table_name']);
		$sb_optimizer_db = Servebolt_Optimize_DB::get_instance();

		if ( $sb_optimizer_db->table_is_innodb($table_name) ) {
			wp_send_json_success([
				'message' => 'Table is already using InnoDB.',
			]);
		} elseif ( ! ( sb_checks() )->table_valid_for_innodb_conversion($table_name) ) {
			wp_send_json_error([
				'message' => 'Specified table is either invalid or unavailable.',
			]);
		} elseif ( $sb_optimizer_db->convert_table_to_innodb($table_name) ) {
			wp_send_json_success([
				'message' => 'Table got converted to InnoDB.',
			]);
		} else {
			wp_send_json_error();
		}
	}

	/**
	 * Trigger database optimization.
	 */
	public function optimize_db_callback() {
		check_ajax_referer(sb_get_ajax_nonce_key(), 'security');
		$result = ( Servebolt_Optimize_DB::get_instance() )->optimize_db();
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
