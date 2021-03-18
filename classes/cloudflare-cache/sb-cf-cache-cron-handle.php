<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Servebolt_CF_Cache_Cron_Handle
 * @package Servebolt
 *
 * This class adds a new interval to the WP cron and schedules the queue-based cache purge method.
 */
class Servebolt_CF_Cache_Cron_Handle {

	/**
	 * Singleton instance.
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Instantiate class.
	 *
	 * @return Servebolt_CF_Cache_Cron_Handle|null
	 */
	public static function get_instance() {
		if ( self::$instance == null ) {
			self::$instance = new Servebolt_CF_Cache_Cron_Handle;
		}
		return self::$instance;
	}

	/**
	 * Servebolt_CF_Cache_Cron_Handle constructor.
	 */
	private function __construct() {
		$this->handle_cron();
	}

	/**
	 * Handle Cloudflare cache purge cron job.
	 */
	private function handle_cron() {

		// Add schedule for execution every minute
		add_filter( 'cron_schedules', [ $this, 'add_cache_purge_cron_schedule' ] );

		// Bail if we are not CLI, not running via cron and not in WP Admin
		if ( !Servebolt\Optimizer\Helpers\isCli() && !Servebolt\Optimizer\Helpers\isCron() && !is_admin()) {
            return;
        }

		// Update cron state
		$this->update_cron_state();

	}

	/**
	 * Update cron state.
	 *
	 * @param bool $blog_id
	 */
	public function update_cron_state($blog_id = false) {

	    if ( ! sb_cf_cache()->cf_is_active($blog_id) || ! sb_cf_cache()->cron_purge_is_active(true, $blog_id) || ! sb_cf_cache()->should_clean_cache_purge_queue() ) {

            // Un-schedule purge queue clean task
            $this->deregister_purge_queue_clean_cron($blog_id);

        } else {

            // Schedule purge queue clean task
            $this->register_purge_queue_clean_cron($blog_id);

        }

		// Check if we should use cron-based cache purging
		if ( ! sb_cf_cache()->cf_is_active($blog_id) || ! sb_cf_cache()->cron_purge_is_active(true, $blog_id) || ! sb_cf_cache()->should_purge_cache_queue() ) {

			// Un-schedule purge task
			$this->deregister_purge_cron($blog_id);

		} else {

			// Schedule purge task
			$this->register_purge_cron($blog_id);

		}
	}

    /**
     * Remove cron-based cache purge queue clean task from schedule.
     *
     * @param bool $blog_id
     */
    public function deregister_purge_queue_clean_cron($blog_id = false) {
        if ( $blog_id ) switch_to_blog( $blog_id );
        $cron_key = sb_cf_cache()->get_purge_queue_clean_cron_key();
        if ( ! wp_next_scheduled($cron_key) ) {
            wp_clear_scheduled_hook($cron_key);
        }
        if ( $blog_id ) restore_current_blog();
    }

    /**
     * Add cron-based cache purge queue clean task from schedule.
     *
     * @param bool $blog_id
     */
    public function register_purge_queue_clean_cron($blog_id = false) {
        if ( $blog_id ) switch_to_blog( $blog_id );
        $cron_key = sb_cf_cache()->get_purge_queue_clean_cron_key();
        add_action( $cron_key, [sb_cf_cache(), 'clean_cache_purge_queue'] );
        if ( ! wp_next_scheduled($cron_key) ) {
            wp_schedule_event( time(), 'daily', $cron_key );
        }
        if ( $blog_id ) restore_current_blog();
    }

	/**
	 * Remove cron-based cache purge task from schedule.
	 *
	 * @param bool $blog_id
	 */
	public function deregister_purge_cron($blog_id = false) {
		if ( $blog_id ) switch_to_blog( $blog_id );
		$cron_key = sb_cf_cache()->get_purge_cron_key();
		if ( ! wp_next_scheduled($cron_key) ) {
			wp_clear_scheduled_hook($cron_key);
		}
		if ( $blog_id ) restore_current_blog();
	}

	/**
	 * Add cron-based cache purge task from schedule.
	 *
	 * @param bool $blog_id
	 */
	public function register_purge_cron($blog_id = false) {
		if ( $blog_id ) switch_to_blog( $blog_id );
		$cron_key = sb_cf_cache()->get_purge_cron_key();
		add_action( $cron_key, [sb_cf_cache(), 'purge_by_cron'] );
		if ( ! wp_next_scheduled($cron_key) ) {
			wp_schedule_event( time(), 'every_minute', $cron_key );
		}
		if ( $blog_id ) restore_current_blog();
	}

	/**
	 * Add cron schedule to be used with Cloudflare cache purging.
	 *
	 * @param $schedules
	 *
	 * @return mixed
	 */
	public function add_cache_purge_cron_schedule( $schedules ) {
		$schedules['every_minute'] = array(
			'interval' => 60,
			'display'  => sb__( 'Every minute' )
		);
		return $schedules;
	}

}
Servebolt_CF_Cache_Cron_Handle::get_instance();
