<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

#require_once SERVEBOLT_PLUGIN_DIR_PATH . '/classes/sb-cloudflare-sdk/sb-cloudflare-sdk.class.php';
require_once __DIR__ . '/sb-cf-cache-purge-queue-handling.php';

use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\naturalLanguageJoin;
use function Servebolt\Optimizer\Helpers\deleteBlogOption;
use function Servebolt\Optimizer\Helpers\updateBlogOption;
use function Servebolt\Optimizer\Helpers\getBlogOption;
use function Servebolt\Optimizer\Helpers\deleteOption;
use function Servebolt\Optimizer\Helpers\updateOption;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\smartUpdateOption;
use function Servebolt\Optimizer\Helpers\smartGetOption;

/**
 * Class Servebolt_CF_Cache
 * @package Servebolt
 *
 * This class handles works as a bridge between WordPress and the Cloudflare API SDK.
 */
class Servebolt_CF_Cache extends Servebolt_CF_Cache_Purge_Queue_Handling {

	/**
	 * Singleton instance.
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Cloudflare wrapper class.
	 *
	 * @var null
	 */
	private $cf = null;

	/**
	 * The default API authentication type.
	 *
	 * @var string
	 */
	private $default_authentication_type = 'api_token';

	/**
	 * Whether we successfully registered credentials for Cloudflare API class.
	 *
	 * @var bool
	 */
	private $credentials_ok = false;

	/**
	 * Blog context - used only in multisite context.
	 *
	 * @var bool
	 */
	private $blog_id = false;

	/**
	 * Instantiate class.
	 *
	 * @return Servebolt_CF_Cache|null
	 */
	public static function get_instance() {
		if ( self::$instance == null ) {
			self::$instance = new Servebolt_CF_Cache(true);
		}
		return self::$instance;
	}

	/**
	 * Servebolt_CF_Cache constructor.
	 *
	 * @param bool $init
	 */
	public function __construct($init = false) {
	    return;
		if ($init) $this->cf_init();
	}

	/**
	 * Instantiate CF class by passing authentication and zone parameters.
	 *
	 * @param bool $blog_id
	 *
	 * @return bool
	 */
	public function cf_init($blog_id = false) {
		$this->blog_id = $blog_id;
		if ( ! $this->register_credentials() ) return false;
		if ( $active_zone = $this->get_active_zone_id() ) $this->cf()->set_zone_id($active_zone, false);
		return true;
	}

	/**
	 * Switch API credentials and zone to the specified blog.
	 *
	 * @param bool $blog_id
	 *
	 * @return bool|null
	 */
	public function cf_switch_to_blog($blog_id = false) {
		if ( $blog_id === false ) {
			return true;
		}
		if ( is_numeric($blog_id) ) {
			$this->cf_init($blog_id);
			return true;
		}
		return null;
	}

	/**
	 * Switch API credentials and zone back to the current blog.
	 */
	public function cf_restore_current_blog() {
		return $this->cf_init(false);
	}

	/**
	 * Get Cloudflare instance.
	 *
	 * @return Servebolt_CF_Cache|null
	 */
	public function cf() {
		if ( is_null($this->cf) ) {
			$this->cf = SB_CF_SDK::get_instance();
		}
		return $this->cf;
	}

	/**
	 * The Cloudflare API permissions required for this plugin.
	 *
	 * @param bool $human_readable
	 *
	 * @return array|string
	 */
	public function api_permissions_needed($human_readable = true) {
		$permissions = ['Zone.Zone', 'Zone.Cache Purge'];
		if ( $human_readable ) {
			return naturalLanguageJoin($permissions);
		}
		return $permissions;
	}

	/**
	 * Test API connection.
	 *
	 * @param bool $auth_type
	 *
	 * @return bool
	 */
	public function test_api_connection($auth_type = false) {
		try {
			if ( ! $auth_type ) {
				$auth_type = $this->get_authentication_type();
			}
			$auth_type = $this->ensure_auth_type_integrity($auth_type);
			switch ( $auth_type ) {
				case 'api_token':
					return $this->cf()->verify_token();
					break;
				case 'api_key':
					return $this->cf()->verify_user();
					break;
			}
			return false;
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Check if we should use Cloudflare feature.
	 *
	 * @return bool
	 */
	public function should_use_cf_feature() {
		return $this->cf_is_active() && $this->cf_cache_feature_available();
	}

	/**
	 * Check that we have credentials and have selected a zone.
	 *
	 * @return bool
	 */
	public function cf_cache_feature_available() {
		return $this->credentials_ok() && $this->zone_ok();
	}

	/**
	 * The option name/key we use to store the active state for the Cloudflare cache feature.
	 *
	 * @return string
	 */
	private function cf_active_option_key() {
		return 'cf_switch';
	}

	/**
	 * Get the blog context with possibility for override.
	 *
	 * @param bool $override
	 *
	 * @return bool
	 */
	protected function get_blog_id($override = false) {
		if ( is_numeric($override) ) return $override;
		return $this->blog_id;
	}

	/**
	 * Check if Cloudflare cache feature is active.
	 *
	 * @param bool $blog_id
	 *
	 * @return bool
	 */
	public function cf_is_active($blog_id = false) {
		$blog_id = $this->get_blog_id($blog_id);
		if ( is_numeric( $blog_id ) ) {
			return checkboxIsChecked(getBlogOption($blog_id, $this->cf_active_option_key()));
		} else {
			return checkboxIsChecked(getOption($this->cf_active_option_key()));
		}
	}

	/**
	 * Check if Cloudflare cache feature is active.
	 *
	 * @param bool $state
	 * @param bool $blog_id
	 *
	 * @return bool
	 */
	public function cf_toggle_active(bool $state, $blog_id = false) {
		$blog_id = $this->get_blog_id($blog_id);
		if ( is_numeric($blog_id) ) {
			return updateBlogOption($blog_id, $this->cf_active_option_key(), $state);
		} else {
			return updateOption($this->cf_active_option_key(), $state);
		}
	}

	/**
	 * Check if we got Cloudflare API credentials in place.
	 *
	 * @return bool
	 */
	public function credentials_ok() {
		return $this->credentials_ok === true;
	}

	/**
	 * Check that we have specified a zone.
	 *
	 * @return bool
	 */
	public function zone_ok() {
		$zone = $this->get_active_zone_id();
		return $zone !== false && ! is_null($zone);
	}

	/**
	 * Get zone by Id from Cloudflare.
	 *
	 * @param $zone_id
	 * @param bool $blog_id
	 *
	 * @return mixed
	 */
	public function get_zone_by_id($zone_id, $blog_id = false) {
		if ( $blog_id ) $this->cf_switch_to_blog($blog_id);
		$zone = $this->cf()->get_zone_by_id($zone_id);
		if ( $blog_id ) $this->cf_restore_current_blog();
		return $zone;
	}

	/**
	 * Clear the active zone Id.
	 *
	 * @param bool $blog_id
	 */
	public function clear_active_zone_id($blog_id = false) {
		$blog_id = $this->get_blog_id($blog_id);
		if ( is_numeric( $blog_id ) ) {
			deleteBlogOption($blog_id,'cf_zone_id');
		} else {
            deleteOption('cf_zone_id');
		}
	}

	/**
	 * Store active zone Id.
	 *
	 * @param $zone_id
	 * @param bool $blog_id
	 *
	 * @return bool
	 */
	public function store_active_zone_id($zone_id, $blog_id = false) {
		if ( is_numeric($blog_id) ) {
			return updateBlogOption($blog_id, 'cf_zone_id', $zone_id);
		} else {
			return updateOption('cf_zone_id', $zone_id);
		}
	}

	/**
	 * Get active zone Id.
	 *
	 * @param bool $blog_id
	 *
	 * @return mixed|void
	 */
	public function get_active_zone_id($blog_id = false) {
		$blog_id = $this->get_blog_id($blog_id);
		if ( is_numeric($blog_id) ) {
			return getBlogOption($blog_id, 'cf_zone_id');
		} else {
			return getOption('cf_zone_id');
		}
	}

	/**
	 * List zones.
	 *
	 * @return mixed
	 */
	public function list_zones() {
		return $this->cf()->list_zones();
	}

	/**
	 * Get authentication type for Cloudflare.
	 *
	 * @param bool $blog_id
	 *
	 * @return mixed|void
	 */
	public function get_authentication_type($blog_id = false) {
		$blog_id = $this->get_blog_id($blog_id);
		if ( is_numeric($blog_id) ) {
			return getBlogOption($blog_id, 'cf_auth_type',  $this->default_authentication_type);
		} else {
			return getOption('cf_auth_type',  $this->default_authentication_type);
		}
	}

	/**
	 * Set authentication type.
	 *
	 * @param $type
	 * @param bool $blog_id
	 *
	 * @return bool
	 */
	public function set_authentication_type($type, $blog_id = false) {
		$blog_id = $this->get_blog_id($blog_id);
		if ( is_numeric($blog_id) ) {
			return updateBlogOption($blog_id, 'cf_auth_type', $type);
		} else {
			return updateOption('cf_auth_type', $type);
		}
	}

	/**
	 * Clear API credentials.
	 *
	 * @param bool $blog_id
	 */
	public function clear_credentials($blog_id = false) {
		$blog_id = $this->get_blog_id($blog_id);
		foreach(['cf_auth_type', 'cf_api_token', 'cf_api_key', 'cf_email'] as $key) {
			if ( is_numeric($blog_id) ) {
				deleteBlogOption($blog_id, $key);
			} else {
                deleteOption($key);
			}
		}
	}

	/**
	 * Register credentials in Cloudflare class.
	 *
	 * @param $auth_type
	 * @param $credentials
	 *
	 * @return mixed
	 */
	public function register_credentials_in_cf_class($auth_type, $credentials) {
		return $this->cf()->set_credentials($auth_type, $credentials);
	}

	/**
	 * Register credentials in this class.
	 *
	 * @param bool $blog_id
	 *
	 * @return bool
	 */
	public function register_credentials($blog_id = false) {
		$blog_id = $this->get_blog_id($blog_id);
		$auth_type = $this->get_authentication_type($blog_id);
		$auth_type = $this->ensure_auth_type_integrity($auth_type);
		switch ( $auth_type ) {
			case 'api_token':
				$api_token = $this->get_credential('api_token', $blog_id);
				if ( ! empty($api_token) ) {
					$this->register_credentials_in_cf_class('api_token', compact('api_token'));
					$this->credentials_ok = true;
				}
				break;
			case 'api_key':
				$email = $this->get_credential('email', $blog_id);
				$api_key = $this->get_credential('api_key', $blog_id);
				if ( ! empty($email) && ! empty($api_key) ) {
					$this->register_credentials_in_cf_class('api_key', compact('email', 'api_key'));
					$this->credentials_ok = true;
				}
				break;
		}
		return $this->credentials_ok;
	}

	/**
	 * Register credentials in this class, but passing in credentials manually.
	 *
	 * @param $auth_type
	 * @param $credentials
	 *
	 * @return bool
	 */
	public function register_credentials_manually($auth_type, $credentials) {
		$auth_type = $this->ensure_auth_type_integrity($auth_type);
		switch ( $auth_type ) {
			case 'api_token':
				$api_token = $credentials['api_token'];
				if ( ! empty($api_token) ) {
					$this->register_credentials_in_cf_class('api_token', compact('api_token'));
					$this->credentials_ok = true;
				}
				break;
			case 'api_key':
				$email = $credentials['email'];
				$api_key = $credentials['api_key'];
				if ( ! empty($email) && ! empty($api_key) ) {
					$this->register_credentials_in_cf_class('api_key', compact('email', 'api_key'));
					$this->credentials_ok = true;
				}
				break;
		}
		return $this->credentials_ok;
	}

	/**
	 * Get credential from DB.
	 *
	 * @param $key
	 * @param bool $blog_id
	 *
	 * @return bool|mixed|void
	 */
	public function get_credential($key, $blog_id = false) {
		switch ($key) {
			case 'email':
				$option_name = 'cf_email';
				break;
			case 'api_key':
				$option_name = 'cf_api_key';
				break;
			case 'api_token':
				$option_name = 'cf_api_token';
				break;
		}
		if ( isset($option_name) ) {
			return smartGetOption($this->get_blog_id($blog_id), $option_name);
		}
		return false;
	}

	/**
	 * Store credential.
	 *
	 * @param $key
	 * @param $value
	 * @param bool $blog_id
	 *
	 * @return bool
	 */
	private function store_credential($key, $value, $blog_id = false) {
		switch ($key) {
			case 'email':
				$option_name = 'cf_email';
				break;
			case 'api_key':
				$option_name = 'cf_api_key';
				break;
			case 'api_token':
				$option_name = 'cf_api_token';
				break;
		}
		if ( isset($option_name) ) {
			return smartUpdateOption($this->get_blog_id($blog_id), $option_name, $value, true);
		}
		return false;
	}

	/**
	 * Store API credentials in DB.
	 *
	 * @param $credentials
	 * @param $auth_type
	 * @param bool $blog_id
	 *
	 * @return bool
	 */
	public function store_credentials($auth_type, $credentials, $blog_id = false) {
		$blog_id = $this->get_blog_id($blog_id);
		$auth_type = $this->ensure_auth_type_integrity($auth_type);
		switch ($auth_type) {
			case 'api_key':
				if ( $this->set_authentication_type($auth_type, $blog_id) && $this->store_credential('email', $credentials['email'], $blog_id) && $this->store_credential('api_key', $credentials['api_key'], $blog_id) ) {
					$this->register_credentials($blog_id);
					return true;
				}
				break;
			case 'api_token':
				if ( $this->set_authentication_type($auth_type, $blog_id) && $this->store_credential('api_token', $credentials['api_token'], $blog_id) ) {
					$this->register_credentials($blog_id);
					return true;
				}
				break;
		}
		return false;
	}

	/**
	 * Make sure auth type is specified correctly.
	 *
	 * @param $auth_type
	 *
	 * @return bool|string
	 */
	private function ensure_auth_type_integrity($auth_type) {
		switch ( $auth_type ) {
			case 'token':
			case 'apiToken':
			case 'api_token':
				return 'api_token';
			case 'key':
			case 'apiKey':
			case 'api_key':
				return 'api_key';
		}
		return false;
	}

    /**
     * @param int $term_id
     * @param string $taxonomy_slug
     * @return bool|mixed
     */
	private function get_purge_urls_by_term_id( int $term_id, string $taxonomy_slug ) {
        return sb_cf_cache_purge_object($term_id, 'term', ['taxonomy_slug' => $taxonomy_slug])->get_urls();
    }

	/**
	 * Get all URL's to purge for a post.
	 *
	 * @param $post_id
	 *
	 * @return array
	 */
	private function get_purge_urls_by_post_id( int $post_id ) {
        return sb_cf_cache_purge_object($post_id, 'post')->get_urls();
	}

    /**
     * Get all urls that are queued up for purging.
     *
     * @param $items
     *
     * @return array
     */
    private function get_purge_urls_from_purge_items(array $items) {
        $urls = [];
        foreach ( $items as $item ) {
            $urls = array_merge($urls, $item->get_purge_urls());
        }
        $urls = array_unique($urls);
        return $urls;
    }

    /**
     * Purging Cloudflare cache on term save.
     *
     * @param int $term_id
     * @param string $taxonomy
     * @return bool
     */
	public function purge_term( int $term_id, string $taxonomy ) {

        // If cron purge is enabled, build the list of ids to purge by cron. If not active, just purge right away.
        if ( $this->cron_purge_is_active() ) {
            return $this->add_term_item_to_purge_queue($term_id);
        } else if ( $urls_to_purge = $this->get_purge_urls_by_term_id($term_id, $taxonomy) ) {
            return $this->cf()->purge_urls($urls_to_purge);
        }

    }

	/**
	 * Purging Cloudflare cache on post save.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return bool|void
	 */
	public function purge_post( int $post_id ) {

		// If this is just a revision, don't purge anything.
		if ( ! $post_id || wp_is_post_revision( $post_id ) ) return false;

		// If cron purge is enabled, build the list of ids to purge by cron. If not active, just purge right away.
		if ( $this->cron_purge_is_active() ) {
		    return $this->add_post_item_to_purge_queue($post_id);
		} else if ( $urls_to_purge = $this->get_purge_urls_by_post_id($post_id) ) {
			return $this->cf()->purge_urls($urls_to_purge);
		}

		return false;
	}

	/**
	 * Purge Cloudflare by URL. Also checks for an archive to purge.
	 *
	 * @param string $url The URL to be purged.
	 *
	 * @return bool|void
	 */
	public function purge_by_url( string $url ) {
		$post_id = url_to_postid( $url );
		if ( $post_id ) {
			return $this->purge_post($post_id);
		} else {
			if ( $this->cron_purge_is_active() ) {
				return $this->add_item_to_purge_queue($url, 'url');
			} else {
				return $this->cf()->purge_urls([$url]);
			}
		}
	}

	/**
	 * Purge Cloudflare by post ID. Also checks for an archive to purge.
	 *
	 * @param int $post_id The post ID for the post to be purged.
	 *
	 * @return bool|void
	 */
	public function purge_by_post( int $post_id ) {
		return $this->purge_post($post_id);
	}

    /**
     * Sort and limit the cache purge queue by a max amount.
     *
     * @param $items
     * @param $limit
     * @return array
     */
	private function limit_items_to_purge($items, $limit) {
        // Make sure items are sorted by date
        usort($items, function ($item1, $item2){
            if ( $item1->get_time_added() == $item2->get_time_added() ) return 0;
            return ( $item1->get_time_added() < $item2->get_time_added() ) ? -1 : 1;
        });

        // Only return the oldest ones
        return array_slice($items, 0, $limit);
    }

	/**
	 * Purging Cloudflare cache by cron using a list of IDs updated.
	 */
	public function purge_by_cron() {
	    if ( apply_filters('sb_optimizer_throttle_queue_items_on_cron_purge', false) ) {
            $max_items_per_request = apply_filters('sb_optimizer_throttle_queue_max_items', 1);
            $all_items = $this->get_items_to_purge();
            $items = $this->limit_items_to_purge($all_items, $max_items_per_request);
            $urls = $this->get_purge_urls_from_purge_items( $items );
            if ( ! empty( $urls ) ) {
                $this->cf()->purge_urls( $urls );
                $this->delete_items_to_purge($items); // Remove items from queue
                return true;
            }
            return false;
        } else {
            $urls = $this->get_purge_urls_from_purge_items( $this->get_items_to_purge() );
            if ( ! empty( $urls ) ) {
                $this->cf()->purge_urls( $urls );
                $this->clear_items_to_purge();
                return true;
            }
            return false;
        }
	}

    /**
     * Generate the timestamp to use when cleaning the cache purge queue.
     *
     * @return bool|string
     */
    private function clean_cache_purge_queue_time_threshold() {
        return apply_filters('sb_optimizer_clean_cache_purge_queue_time_threshold', ( current_time('timestamp') - WEEK_IN_SECONDS ));
    }

    /**
     * Clean the cache purge queue of items that are older than the defined threshold.
     */
    public function clean_cache_purge_queue() {
        $threshold = $this->clean_cache_purge_queue_time_threshold();
        if ( is_numeric($threshold) ) {
            $items = $this->get_items_to_purge_unformatted();
            $init_item_count = count($items);
            $items = array_filter($items, function($item) use ($threshold) {
                if ( ! $item['timestamp'] ) { // Remove items that has no creation datetime
                    return false;
                }
                if ( $threshold > $item['timestamp'] ) { // Remove items that are older than the threshold
                    return false;
                }
                return true;
            });
            $this->set_items_to_purge($items);
            return $init_item_count !== count($items) ? true : null; // Indicate whether something changed
        }
        return false;
    }

    /**
     * Purge all.
     *
     * @return mixed
     */
    public function purge_all() {
        if ( $this->cron_purge_is_active() ) {
            return $this->add_purge_all_to_purge_queue();
        } else {
            return $this->cf()->purge_all();
        }
    }

}
