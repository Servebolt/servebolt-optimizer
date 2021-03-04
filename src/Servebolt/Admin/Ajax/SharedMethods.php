<?php

namespace Servebolt\Optimizer\Admin\Ajax;

abstract class SharedMethods
{
    protected function checkAjaxReferer() : void
    {
        check_ajax_referer( sb_get_ajax_nonce_key(), 'security' );
    }
}
