<?php

namespace Servebolt\Optimizer\Admin;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\getAjaxNonceKey;

/**
 * Class SharedAjaxMethods
 * @package Servebolt\Optimizer\Admin
 */
abstract class SharedAjaxMethods
{
    protected function checkAjaxReferer() : void
    {
        check_ajax_referer(getAjaxNonceKey(), 'security');
    }
}
