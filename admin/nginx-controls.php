<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Nginx_Controls
 */
class Nginx_Controls {

	/**
	 * @var null Singleton instance.
	 */
	private static $instance = null;

	/**
	 * Singleton instantiation.
	 *
	 * @return Nginx_Controls|null
	 */
	public static function instance() {
		if ( self::$instance == null ) {
			self::$instance = new Nginx_Controls;
		}
		return self::$instance;
	}

	/**
	 * Nginx_Controls constructor.
	 */
	public function __construct() {
		$this->add_assets();
	}

	/**
	 * Add assets.
	 */
	public function add_assets() {
		add_action('admin_head', [$this, 'add_scripts']);
	}

	/**
	 * Add scripts.
	 */
	public function add_scripts() {
		?>
		<script type="text/javascript" >
          jQuery(document).ready(function($) {
            $('#nginx_cache_switch').change(function(){
              var form = $('#post-types-form');
              if ( $(this).is(':checked') ) {
                form.show();
              } else {
                form.hide();
              }
            });
          });
		</script>
		<?php
	}

	/**
	 * Display performance checks view.
	 */
	public function view() {
		sb_view('admin/views/nginx-controls', [
			'sites'        => is_network_admin() ? get_sites() : [],
			'options'      => sb_get_option('fpc_settings'),
		  'post_types'   => get_post_types(['public' => true], 'objects'),
	    'nginx_switch' => sb_get_option('fpc_switch') === 'on',
      'sb_admin_url' => get_sb_admin_url(),
		]);
	}

}
Nginx_Controls::instance();
