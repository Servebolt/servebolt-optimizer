<?php

namespace Servebolt\Optimizer\Compatibility\YoastPremium;

use Servebolt\Optimizer\CachePurge\WordPressCachePurge\WordPressCachePurge;
use Throwable;
use function Servebolt\Optimizer\Helpers\arrayGet;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class RedirectCachePurge
 * @package Servebolt\Optimizer\Compatibility\Yoast
 */
class RedirectCachePurge
{
    /**
     * RedirectCachePurge constructor.
     */
    public function __construct()
    {
        add_action('Yoast\WP\SEO\redirects_modified', [$this, 'purgeCacheOnRedirectModification'], 10, 3);
    }

    /**
     * Check whether we're handling a regex redirect. Yes, this is a but hacky and ugly. I'll try to make Yoast SEO.
     *
     * @return bool
     */
    private function isRegexRedirect(): bool
    {
        if (did_action('wp_ajax_wpseo_add_redirect_regex')) {
            return true;
        }
        if ($inputData = file_get_contents('php://input')) {
            $postData = json_decode($inputData, true);
            if($postData && json_last_error() === JSON_ERROR_NONE) {
                if (arrayGet('format', $postData) === 'regex') {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Purge cache for URLs that are redirected by Yoast SEO Premium.
     *
     * @param string|null $origin The from-redirect URL.
     * @param string|null $target The to-redirect URL.
     * @param int $type The HTTP redirect code.
     */
    public function purgeCacheOnRedirectModification(?string $origin, ?string $target, int $type): void
    {
        if ($this->isRegexRedirect()) {
            return;
        }
        if ($origin) {
            $this->purgeUrl($origin);
        }
        if ($target) {
            $this->purgeUrl($target);
        }
    }

    /**
     * Attempt to purge URL.
     *
     * @param $url
     */
    private function purgeUrl($url): void
    {
        try {
            if ($url) {
                WordPressCachePurge::purgeByUrl(
                    $this->formatUrl((string) $url),
                    false
                );
            }
        } catch (Throwable $e) {}
    }

    /**
     * Make sure that URL has right format before purging cache.
     *
     * @param string $rawUrl
     * @return string
     */
    private function formatUrl(string $rawUrl): string
    {
        if (mb_substr($rawUrl, 0, 4) === 'http') {
            return $rawUrl;
        }
        $url = get_site_url() . '/' . ltrim($rawUrl, '/');
        return apply_filters('sb_optimizer_yoast_seo_premium_redirect_url_format', $url, $rawUrl);
    }
}
