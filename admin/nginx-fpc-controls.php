<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Nginx_FPC_Controls
 *
 * This class displays the Nginx Full Page Cache control GUI  - only works for sites hosted at Servebolt.
 */
class Nginx_FPC_Controls {

	/**
	 * @var null Singleton instance.
	 */
	private static $instance = null;

	/**
	 * Singleton instantiation.
	 *
	 * @return Nginx_FPC_Controls|null
	 */
	public static function get_instance() {
		if ( self::$instance == null ) {
			self::$instance = new Nginx_FPC_Controls;
		}
		return self::$instance;
	}

	/**
	 * Nginx_FPC_Controls constructor.
	 */
	private function __construct() {
		$this->init_settings();
		$this->init_assets();
		$this->add_ajax_handling();
	}

	/**
	 * Register AJAX handling.
	 */
	private function add_ajax_handling() {
		add_action('wp_ajax_servebolt_fpc_exclude_post', [$this, 'exclude_posts_callback']);
		add_action('wp_ajax_servebolt_update_fpc_exclude_posts_list', [$this, 'update_fpc_exclude_posts_list_callback']);
	}

	/**
	 * Init assets.
	 */
	private function init_assets() {
		add_action('admin_enqueue_scripts', [$this, 'plugin_scripts']);
	}

	/**
	 * Plugin scripts.
	 */
	public function plugin_scripts() {
		$screen = get_current_screen();
		if ( $screen->id != 'servebolt_page_servebolt-nginx-cache' ) return;
		wp_enqueue_script( 'servebolt-optimizer-fpc-scripts', SERVEBOLT_PATH_URL . 'assets/dist/js/fpc.js', ['servebolt-optimizer-scripts'], filemtime(SERVEBOLT_PATH . 'assets/dist/js/fpc.js'), true );
	}

	/**
	 * Update FPC post exclude list.
	 */
	public function update_fpc_exclude_posts_list_callback() {
		check_ajax_referer( sb_get_ajax_nonce_key(), 'security' );
		sb_ajax_user_allowed();

		$items_to_remove = sb_array_get('items', $_POST);
		if ( $items_to_remove === 'all' ) {
			sb_nginx_fpc()->set_ids_to_exclude_from_cache([]);
			wp_send_json_success();
		}
		if ( ! $items_to_remove || empty($items_to_remove) ) wp_send_json_error();
		if ( ! is_array($items_to_remove) ) $items_to_remove = [];
		$items_to_remove = array_filter($items_to_remove, function ($item) {
			return is_numeric($item);
		});
		$current_items = sb_nginx_fpc()->get_ids_to_exclude_from_cache();
		if ( ! is_array($current_items) ) $current_items = [];
		$updated_items = array_filter($current_items, function($item) use ($items_to_remove) {
			return ! in_array($item, $items_to_remove);
		});
		sb_nginx_fpc()->set_ids_to_exclude_from_cache($updated_items);
		wp_send_json_success();
	}

	/**
	 * Add post IDs to be excluded from FPC.
	 */
	public function exclude_posts_callback() {
		check_ajax_referer( sb_get_ajax_nonce_key(), 'security' );
		sb_ajax_user_allowed();

		$post_ids_string = sb_array_get('post_ids', $_POST);
		$post_ids = sb_format_comma_string($post_ids_string);

		if ( ! $post_ids || empty($post_ids) ) {
			wp_send_json_error([
				'message' => __('Post IDs missing'),
			]);
		}

		$only_one_post_id = count($post_ids) === 1;
		$invalid          = [];
		$failed           = [];
		$already_excluded = [];
		$added            = [];
		$success          = [];
		$new_markup       = '';

		foreach ( $post_ids as $post_id) {

			if ( ! is_numeric($post_id) || ! $post = get_post($post_id) ) {
				$invalid[] = $post_id;
				continue;
			}

			if ( sb_nginx_fpc()->should_exclude_post_from_cache($post_id) ) {
				$already_excluded[] = $post_id;
				$success[] = $post_id;
				continue;
			}

			if ( sb_nginx_fpc()->exclude_post_from_cache($post_id) ) {
				$new_markup .= fpc_exclude_post_table_row_markup($post_id, false);
				$success[] = $post_id;
				$added[] = $post_id;
				continue;
			}

			$failed[] = $post_id;
		}

		$got_success = count($success);
		$has_invalid = count($invalid) > 0;
		$has_failed  = count($failed) > 0;
		$has_invalid_only = ! $got_success && $has_invalid && ! $has_failed;
		$has_failed_only = ! $got_success && $has_failed && ! $has_invalid;

		$type = 'success';
		$title = sb__('All good');
		$return_method = 'wp_send_json_success';

		$invalid_message = '';
		$failed_message = '';
		$already_excluded_message = '';
		$added_message = '';

		if ( $has_invalid ) {
			$type = 'warning';
			$title = sb__('We made progress, but...');
			$invalid_message = sprintf(sb__('The following %s were invalid:'), ( $only_one_post_id ? sb__('post ID') : sb__('post IDs') ) ) . sb_create_li_tags_from_array($invalid);
		}

		if ( $has_failed ) {
			$type = 'warning';
			$title = sb__('We made progress, but...');
			$failed_message = sb__('Could not exclude the following posts from cache:') . sb_create_li_tags_from_array($failed, function($post_id) {
				$title = get_the_title($post_id);
				return $title ? $title . ' (ID ' . $post_id . ')' : $post_id;
			});
		}

		if ( count($already_excluded) > 0 ) {
			$already_excluded_message = sb__('The following posts are already excluded from cache:') . sb_create_li_tags_from_array($already_excluded, function($post_id) {
				$title = get_the_title($post_id);
				return $title ? $title . ' (ID ' . $post_id . ')' : $post_id;
			});
		}

		if ( count($added) > 0 ) {
			$added_message = sb__('The following posts were excluded from cache:') . sb_create_li_tags_from_array($added, function($post_id) {
				$title = get_the_title($post_id);
				return $title ? $title . ' (ID ' . $post_id . ')' : $post_id;
			});
		}

		if ( $has_invalid_only ) {
			$return_method = 'wp_send_json_error';
			$type = 'warning';
			$title = ( $only_one_post_id ? sb__('Post ID invalid') : sb__('Post IDs invalid') );
		} elseif ( $has_failed_only ) {
			$return_method = 'wp_send_json_error';
			$title = sb__('Could not update exclude list');
			$type = 'error';

		} elseif ( ! $got_success ) {
			$return_method = 'wp_send_json_error';
			$title = sb__('Something went wrong');
			$type = 'warning';
		}

		$message = $invalid_message . $failed_message . $already_excluded_message . $added_message;

		$return_method([
			'type'       => $type,
			'title'      => $title,
			'message'    => $message,
			'row_markup' => $new_markup,
		]);
	}

	/**
	 * Initialize settings.
	 */
	private function init_settings() {
		add_action( 'admin_init', [$this, 'register_settings'] );
	}

	/**
	 * Register custom option.
	 */
	public function register_settings() {
		foreach(['fpc_settings', 'fpc_switch'] as $key) {
			register_setting('nginx-fpc-options-page', sb_get_option_name($key));
		}
	}

	/**
	 * Display view.
	 */
	public function view() {
		sb_view('admin/views/nginx-fpc-controls', [
			'sb_admin_url' => Servebolt\Optimizer\Helpers\sbGetAdminUrl(),
		]);
	}

}
Nginx_FPC_Controls::get_instance();
