<?php

namespace Servebolt\Optimizer\Exceptions\MessageTranslator;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class ServeboltMessageTranslation
 * @package Servebolt\Optimizer\Exceptions\MessageTranslator
 */
class ServeboltMessageTranslation extends MessageTranslator
{
    /**
     * Translation definitions.
     *
     * @var array
     */
    protected $definitions = [
        '1012' => 'This is a translated message',
    ];
}
