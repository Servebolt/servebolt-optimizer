<?php

namespace Servebolt\Optimizer\MenuOptimizer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Trait SharedMethods
 * @package Servebolt\Optimizer\MenuOptimizer
 */
trait SharedMethods
{
    /**
     * The version key used in the transient keys so we can easily purge all cache.
     *
     * @var int
     */
    private static $transientVersion = '1.1';

    /**
     * The TTL for the menu signature index transient (default 180 days).
     *
     * @var int
     */
    private static $menuSignatureIndexCacheExpirationTime = 15552000;

    /**
     * The TTL for the menu markup transient (default no expiration).
     *
     * @var int
     */
    private static $menuMarkupCacheExpirationTime = 0;

    /**
     * Get cached menu.
     *
     * @param $menuSignature
     * @return mixed
     */
    private static function getMenuCache($menuSignature)
    {
        return get_transient(self::menuCacheTransientKey($menuSignature));
    }

    /**
     * Set menu cache.
     *
     * @param $navMenu
     * @param $menuSignature
     */
    private static function setMenuCache($navMenu, $menuSignature)
    {
        set_transient(
            self::menuCacheTransientKey($menuSignature),
            $navMenu,
            self::getMenuMarkupCacheExpirationTime()
        );
    }

    /**
     * Get menu signature index by menu Id.
     *
     * @param int $menuId
     * @return array
     */
    private static function getMenuSignatureIndex($menuId)
    {
        $transientKey = self::menuSignatureIndexTransientKey($menuId);
        $menuSignatureIndex = get_transient($transientKey);
        return $menuSignatureIndex === false ? [] : json_decode($menuSignatureIndex);
    }

    /**
     * Set menu signature index.
     *
     * @param $menuSignatureIndex
     * @param $menuId
     */
    private static function setMenuSignatureIndex($menuSignatureIndex, $menuId): void
    {
        $transientKey = self::menuSignatureIndexTransientKey($menuId);
        set_transient(
            $transientKey,
            wp_json_encode($menuSignatureIndex),
            self::getMenuSignatureIndexCacheExpirationTime()
        );
    }

    /**
     * Delete menu signature index.
     *
     * @param $menuId
     */
    private static function deleteMenuSignatureIndex($menuId): void
    {
        $transientKey = self::menuSignatureIndexTransientKey($menuId);
        delete_transient($transientKey);
    }

    /**
     * Add a menu signature to the menu signature index.
     *
     * @param $menuSignature
     * @param $menuId
     */
    private static function addMenuSignatureToIndex($menuSignature, $menuId)
    {
        $menuSignatureIndex = self::getMenuSignatureIndex($menuId);
        if (!in_array($menuSignature, $menuSignatureIndex, true)) {
            $menuSignatureIndex[] = $menuSignature;
        }
        self::setMenuSignatureIndex($menuSignatureIndex, $menuId);
    }

    /**
     * Get the expiration in seconds for storing the menu markup.
     *
     * @return int
     */
    private static function getMenuMarkupCacheExpirationTime(): int
    {
        return apply_filters(
            'sb_optimizer_menu_optimizer_menu_markup_expiration_time',
            self::$menuMarkupCacheExpirationTime
        );
    }

    /**
     * Get the expiration in seconds for storing the menu signature index.
     *
     * @return int
     */
    private static function getMenuSignatureIndexCacheExpirationTime(): int
    {
        return apply_filters(
            'sb_optimizer_menu_optimizer_menu_signature_index_expiration_time',
            self::$menuSignatureIndexCacheExpirationTime
        );
    }

    /**
     * Get transient key for storing menu markup.
     *
     * @param string $menuSignature
     * @return string
     */
    private static function menuCacheTransientKey(string $menuSignature): string
    {
        return 'sb-menu-cache-' . $menuSignature . '-v' . self::$transientVersion;
    }

    /**
     * Get transient key for storing menu signature index.
     *
     * @param int $menuId
     * @return string
     */
    private static function menuSignatureIndexTransientKey(int $menuId): string
    {
        return 'sb-menu-cache-menu-id-' . $menuId . '-v' . self::$transientVersion;
    }

    /**
     * Get menu signature based on arguments with a filter to allow 3rd party developers to determine the menu signature and alter the cache behaviour.
     *
     * @return string
     */
    private static function getMenuSignatureFromArgs(): string
    {
        global $wp_query;
        $signatureBase = md5(wp_json_encode(self::$args) . $wp_query->query_vars_hash);

        /**
         * @param string $signatureBase The base of the menu signature.
         * @param object $args The arguments used to create menu signature.
         */
        return apply_filters(
            'sb_optimizer_menu_optimizer_menu_signature',
            $signatureBase,
            self::$args
        );
    }
}
