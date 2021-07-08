<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Servebolt_Optimizer
 */

define('WP_TESTS_ARE_RUNNING', true);
define('WP_TESTS_DIR', __DIR__);
//define('WP_TESTS_THEME', 'twentytwentyone');
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	//$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
    $_tests_dir = __DIR__ . '/bin/tmp/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/servebolt-optimizer.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

if (defined('WP_TESTS_THEME') && !empty(WP_TESTS_THEME)) {
    /**
     * Manually set theme.
     *
     * @return string
     */
    function _set_theme() {
        return WP_TESTS_THEME;
    }

    tests_add_filter( 'stylesheet', '_set_theme' );
    tests_add_filter( 'template', '_set_theme' );
}

// Allow SVG upload for testing purposes
require __DIR__ . '/allow-svg-uploads.php';

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

// Include custom traits
foreach([__DIR__ . '/Unit/Traits/*Trait.php', __DIR__ . '/Feature/Traits/*Trait.php'] as $traitFolder) {
    foreach (glob($traitFolder) as $traitFile) {
        require_once $traitFile;
    }
}

// Include testcase class
require __DIR__ . '/ServeboltWPUnitTestCase.php';
