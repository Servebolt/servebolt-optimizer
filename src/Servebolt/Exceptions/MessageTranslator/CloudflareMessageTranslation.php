<?php

namespace Servebolt\Optimizer\Exceptions\MessageTranslator;

/**
 * Class CloudflareMessageTranslation
 * @package Servebolt\Optimizer\Exceptions\MessageTranslator
 */
class CloudflareMessageTranslation extends MessageTranslator
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
