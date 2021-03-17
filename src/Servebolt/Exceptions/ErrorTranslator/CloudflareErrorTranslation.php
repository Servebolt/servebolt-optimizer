<?php

namespace Servebolt\Optimizer\Exceptions\ErrorTranslator;

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
        '1012' => 'This is a translated error',
    ];
}
