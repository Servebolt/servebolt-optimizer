<?php

namespace Servebolt\Optimizer\Compatibility\EasyDigitalDownloads;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\easyDigitalDownloadsIsActive;

/**
 * Class EasyDigitalDownloads
 * @package Servebolt\Optimizer\Compatibility\EasyDigitalDownloads
 */
class EasyDigitalDownloads
{
    /**
     * EasyDigitalDownloads constructor.
     */
    public function __construct()
    {
        if (!apply_filters('sb_optimizer_easy_digital_downloads_compatibility', true)) {
            return;
        }
        if (!easyDigitalDownloadsIsActive()) {
            return;
        }
        add_action('template_redirect', function () {
            if (edd_is_checkout()) {
                echo '<h1>edd checkout</h1>';
            }
            if (edd_is_success_page()) {
                echo '<h1>edd success page</h1>';
            }
            if (edd_is_failed_transaction_page()) {
                echo '<h1>edd transaction page</h1>';
            }
            if (edd_is_purchase_history_page()) {
                echo '<h1>edd purchase history page</h1>';
            }
            if (edd_is_purchase_history_page()) {
                echo '<h1>edd purchase history page</h1>';
            }
        });
    }
}
