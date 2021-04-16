<?php

namespace Servebolt\Optimizer\Exceptions\ErrorTranslator;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class CloudflareErrorTranslation
 * @package Servebolt\Optimizer\Exceptions\ErrorTranslator
 */
class CloudflareErrorTranslation extends ErrorTranslator
{
    /**
     * Translation definitions.
     *
     * @var array
     */
    protected $definitions = [
        //'1012' => 'This is a translated error',
    ];
}
