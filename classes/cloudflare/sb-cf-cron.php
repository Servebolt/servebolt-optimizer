<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Servebolt_CF_Cron_Handle
 * @package Servebolt
 *
 * This class registers WP cron schedule and task.
 */
class Servebolt_CF_Cron_Handle {

	/**
	 * Singleton instance.
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Instantiate class.
	 *
	 * @return Servebolt_CF_Cron_Handle|null
	 */
	public static function get_instance() {
		if ( self::$instance == null ) {
			self::$instance = new Servebolt_CF_Cron_Handle;
		}
		return self::$instance;
	}

	/**
	 * Servebolt_CF_Cron_Handle constructor.
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

		// Update cron state
		$this->update_cron_state();

	}

	/**
	 * Update cron state.
	 */
	public function update_cron_state() {

		// Check if we should use cron-based cache purging
		if ( ! sb_cf()->cf_is_active() || ! sb_cf()->cron_purge_is_active() || ! sb_cf()->should_purge_cache_queue() ) {

			// Un-schedule task
			$this->deregister_cron();

		} else {

			// Schedule task
			$this->register_cron();

		}
	}

	/**
	 * Remove cron-based cache purge task from schedule.
	 */
	public function deregister_cron() {
		$cron_key = sb_cf()->get_cron_key();
		if ( ! wp_next_scheduled($cron_key) ) {
			wp_clear_scheduled_hook($cron_key);
		}
	}

	/**
	 * Add cron-based cache purge task from schedule.
	 */
	public function register_cron() {
		$cron_key = sb_cf()->get_cron_key();
		add_action( $cron_key, [sb_cf(), 'purge_by_cron'] );
		if ( ! wp_next_scheduled($cron_key) ) {
			wp_schedule_event( time(), 'every_minute', $cron_key );
		}
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
Servebolt_CF_Cron_Handle::get_instance();
