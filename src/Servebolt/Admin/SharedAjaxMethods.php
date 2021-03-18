<?php

namespace Servebolt\Optimizer\Admin;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class SharedAjaxMethods
 * @package Servebolt\Optimizer\Admin
 */
abstract class SharedAjaxMethods
{
    protected function checkAjaxReferer() : void
    {
        check_ajax_referer(sb_get_ajax_nonce_key(), 'security');
    }
}
