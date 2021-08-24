<?php

namespace Servebolt\Optimizer\Admin\AdminBarGui;

if (!defined('ABSPATH')) exit;

/**
 * Interface NodeInterface
 * @package Servebolt\Optimizer\Admin\AdminBarGui
 */
interface NodeInterface
{
    public static function shouldDisplayNodes(): bool;
    public static function generateNodes(): ?array;
}
