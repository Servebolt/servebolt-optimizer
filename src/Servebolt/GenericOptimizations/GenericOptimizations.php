<?php

namespace Servebolt\Optimizer\GenericOptimizations;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class GenericOptimizations
 * @package Servebolt\Optimizer\GenericOptimizations
 */
class GenericOptimizations
{
    /**
     * GenericOptimizations constructor.
     */
    public function __construct()
    {
        if (apply_filters('sb_optimizer_skip_generic_optimizations', false)) {
            return;
        }

        // Disable CONCATENATE_SCRIPTS to get rid of some DDOS-attacks
        if (apply_filters('sb_optimizer_generic_optimizations_concatenate_scripts_disable', true) && !defined('CONCATENATE_SCRIPTS')) {
            define('CONCATENATE_SCRIPTS', false);
        }

        // Hide the meta tag generator from head and RSS
        if (apply_filters('sb_optimizer_generic_optimizations_disable_meta_tag_generator', true)) {
            add_filter('the_generator', '__return_empty_string');
            remove_action('wp_head', 'wp_generator');
        }
    }
}
