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
     * The TTL for the menu markup transient (default 180 days).
     *
     * @var int
     */
    private static $menuMarkupCacheExpirationTime = 15552000;

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
        set_transient(
            self::menuSignatureIndexTransientKey($menuId),
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
        delete_transient(self::menuSignatureIndexTransientKey($menuId));
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
        return (int) apply_filters(
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
        return (int) apply_filters(
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
    private static function menuSignatureIndexTransientKey($menuId): string
    {
        return 'sb-menu-cache-menu-id-' . $menuId . '-v' . self::$transientVersion;
    }

    /**
     * Generate a seed from URI / query string to be used in the menu signature.
     *
     * @return null|string
     */
    private static function querySeed(): ?string
    {
        if (apply_filters('sb_optimizer_menu_optimizer_use_query_vars_hash_for_query_seed', false)) {
            global $wp_query;
            return $wp_query->query_vars_hash;
        }
        global $wp;
        $string = $wp->request;
        $permalinkStructure = get_option('permalink_structure');
        if (
            !$permalinkStructure
            || apply_filters('sb_optimizer_menu_optimizer_add_query_string_to_query_seed', false, $permalinkStructure)
        ) {
            $string .= '?' . $wp->query_string;
        }
        return $string;
    }

    /**
     * Get menu signature based on arguments with a filter to allow 3rd party developers to determine the menu signature and alter the cache behaviour.
     *
     * @return string
     */
    private static function getMenuSignatureFromArgs(): string
    {
        if (WpMenuOptimizer::useSimpleMenuSignature()) {
            $signatureBase = md5(wp_json_encode(self::$args));
        } else {
            $signatureBase = '';
            if (
                is_404()
                && apply_filters('sb_optimizer_menu_optimizer_simplify_signature_for_404', true)
            ) {
                $signatureBase .= '404-not-found-'; // Add a prefix for the menu signatures during 404
            } elseif (
                is_search()
                && apply_filters('sb_optimizer_menu_optimizer_simplify_signature_for_search', true)
            ) {
                $signatureBase .= 'search-'; // Add a prefix for the menu signatures during search
            }
            $signatureBase .= md5(wp_json_encode(self::$args) . self::querySeed());
        }

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
