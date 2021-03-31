<?php

namespace Servebolt\Optimizer\Cli\CliKeyValueStorage;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Interface CliKeyValueStorageInterface
 * @package Servebolt\Optimizer\Cli\CliKeyValueStorage
 */
interface CliKeyValueStorageInterface
{
    public function list($args, $assocArgs);
    public function get($args, $assocArgs);
    public function set($args, $assocArgs);
    public function getSettingsKeys(): array;
    public function getSetting(string $settingKey, ?int $blogId = null);
    public function setSetting(string $settingKey, $value, ?int $blogId = null): bool;
    public function getSettings(): array;
}
