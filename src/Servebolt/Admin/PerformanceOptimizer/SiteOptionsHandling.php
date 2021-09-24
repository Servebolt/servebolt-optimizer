<?php

namespace Servebolt\Optimizer\Admin\PerformanceOptimizer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\getOptionName;

/**
 * Class SiteOptionsHandling
 * @package Servebolt\Optimizer\Admin\PerformanceOptimizer
 */
class SiteOptionsHandling
{

    /**
     * @var string[] List of options to store.
     */
    private $options = [
        'action_scheduler_unix_cron_active',
    ];

    /**
     * @var string Name of page.
     */
    private $page = 'servebolt-performance-optimizer-advanced';

    /**
     * SiteOptionsHandling constructor.
     */
    public function __construct()
    {
        add_action('network_admin_edit_' . $this->page, [$this, 'handleSubmit']);
        add_action('network_admin_notices', [$this, 'adminNotifications']);
    }

    /**
     * Display admin notice after saving site options.
     */
    public function adminNotifications(): void
    {
        if (
            (get_current_screen())->id == 'admin_page_' . $this->page . '-network'
            && isset( $_GET['updated'] )
        ) {
            echo '<div id="message" class="updated notice is-dismissible"><p>' . __('Settings saved.', 'servebolt-wp') . '</p></div>';
        }
    }

    /**
     * Handle submit data and storage.
     */
    public function handleSubmit(): void
    {
        check_admin_referer($this->page); // Nonce security check
        if ($this->options) {
            foreach ($this->options as $option) {
                $option = getOptionName($option);
                $value  = null;
                if (isset($_POST[$option])) {
                    $value = $_POST[$option];
                    if (!is_array($value)) {
                        $value = trim($value);
                    }
                    $value = wp_unslash($value);
                }
                update_site_option($option, $value);
            }
        }
        wp_redirect(
            add_query_arg(
                [
                    'page' => $this->page,
                    'updated' => true
                ],
                network_admin_url('admin.php')
            )
        );
        exit;
    }
}
