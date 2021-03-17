<?php

namespace Servebolt\Optimizer\CachePurge\Drivers;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Servebolt\Optimizer\Api\Servebolt\Servebolt as ServeboltApi;
use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeInterface;
use Servebolt\Optimizer\Exceptions\ServeboltApiError;

class Servebolt implements CachePurgeInterface
{
    use Singleton;

    private $apiInstance;

    /**
     * Servebolt constructor.
     */
    public function __construct()
    {
        $this->apiInstance = ServeboltApi::getInstance();
    }

    /**
     * Check whether the Servebolt SDK is configured correctly.
     *
     * @return bool
     */
    public function configurationOk(): bool
    {
        return $this->apiInstance->isConfigured();
    }

    /**
     * @param string $url
     * @return bool
     * @throws ServeboltApiError
     */
    public function purgeByUrl(string $url): bool
    {
        $response = $this->apiInstance->environment()->purgeCache([$url]);
        if ($response->wasSuccessful()) {
            return true;
        } else {
            throw new ServeboltApiError($response->getErrors(), $response);
        }
    }

    /**
     * @param array $urls
     * @return bool
     * @throws ServeboltApiError
     */
    public function purgeByUrls(array $urls): bool
    {
        $response = $this->apiInstance->environment->purgeCache(
            $this->apiInstance->getEnvironmentId(),
            $urls
        );
        if ($response->wasSuccessful()) {
            return true;
        } else {
            throw new ServeboltApiError($response->getErrors(), $response);
        }
    }

    /**
     * Purge all cache (for a single site).
     *
     * @return bool
     * @throws ServeboltApiError
     */
    public function purgeAll(): bool
    {
        $response = $this->apiInstance->environment->purgeCache(
            $this->apiInstance->getEnvironmentId(),
            [],
            []//$this->getPurgeAllPrefixes()
        );
        if ($response->wasSuccessful()) {
            return true;
        } else {
           throw new ServeboltApiError($response->getErrors(), $response);
        }
    }

    /**
     * Purge cache for all sites in multisite-network.
     *
     * @return bool
     * @throws ServeboltApiError
     */
    public function purgeAllNetwork(): bool
    {
        $response = $this->apiInstance->environment->purgeCache(
            $this->apiInstance->getEnvironmentId(),
            [],
            $this->getPurgeAllPrefixesWithMultisiteSupport()
        );
        if ($response->wasSuccessful()) {
            return true;
        } else {
            throw new ServeboltApiError($response->getErrors(), $response);
        }
    }

    /**
     * Allow for custom handling of prefixes when purging all cache.
     *
     * @param bool $isMultisite
     * @return array|bool
     */
    private function purgeAllPrefixOverride(bool $isMultisite = false)
    {
        $override = apply_filters('sb_optimizer_acd_purge_all_prefixes_early_override', [], $isMultisite);
        if (is_array($override) && !empty($override)) {
            return $override;
        }
        return false;
    }

    /**
     * Build array of prefix URLs when purging all cache for a site.
     *
     * @return array
     */
    public function getPurgeAllPrefixes(): array
    {
        if ($override = $this->purgeAllPrefixOverride(false)) {
            return $override;
        }
        $siteUrl = get_site_url();
        $siteDomain = $this->extractDomainFromUrl($siteUrl);
        $prefixes = [$siteDomain];
        if (apply_filters('sb_optimizer_add_www_domains_on_acd_purge_all', true)) {
            $prefixes = $this->addWwwDomains($prefixes);
        }
        $prefixes = apply_filters('sb_optimizer_acd_purge_all_prefixes', $prefixes);
        $prefixes = apply_filters('sb_optimizer_acd_purge_all_prefixes_single_site', $prefixes);
        return $prefixes;
    }

    /**
     * Build array of prefix URLs when purging all cache for a site.
     *
     * @return array
     */
    public function getPurgeAllPrefixesWithMultisiteSupport(): array
    {
        if ($override = $this->purgeAllPrefixOverride(true)) {
            return $override;
        }
        if (!is_multisite()) {
            return [];
        }
        $prefixes = $this->getDomainsWithThirdPartySupport(); // All domains in a multisite-setup
        if (apply_filters('sb_optimizer_add_www_domains_on_acd_purge_all', true)) {
            $prefixes = $this->addWwwDomains($prefixes);
        }
        $prefixes = apply_filters('sb_optimizer_acd_purge_all_prefixes', $prefixes);
        $prefixes = apply_filters('sb_optimizer_acd_purge_all_prefixes_multisite', $prefixes);
        return $prefixes;
    }

    /**
     * @return array
     */
    private function getDomainsWithThirdPartySupport(): array
    {
        $sites = array_map( 'get_object_vars', get_sites( array( 'deleted' => 0 ) ) ); //WP 4.6+
        $domains = [];
        foreach ($sites as $site) {

            // Multisite Domain
            $domains[] = $site['domain'];

            // Support for Mercator
            if (class_exists('\\Mercator\\Mapping') && $mappings = \Mercator\Mapping::get_by_site($site['blog_id'])) {
                foreach ( $mappings as $mapping ) {
                    if ( $mapping->is_active() ) {
                        if ($domain = $mapping->get_domain()) {
                            $domains[] = $domain;
                        }
                    }
                }
            }

            // Support for WordPress MU Domain Mapping
            if (function_exists('\\domain_mapping_siteurl') && switch_to_blog($site['blog_id'])) {
                if ($siteUrl = domain_mapping_siteurl(false)) {
                    if ($domain = $this->extractDomainFromUrl($siteUrl)) {
                        $domains[] = $domain;
                    }
                }
                restore_current_blog();
            }
        }

        $domains = array_unique($domains); // Remove duplicates

        return $domains;
    }

    /**
     * Add www-domains to domains that does not have a subdomain and that is not already a www-domain.
     *
     * @param $domains
     * @return array
     */
    private function addWwwDomains($domains): array
    {
        foreach ($domains as $domain) {
            if (substr_count($domain, '.') === 1 && substr($domain, 0, 3) !== 'www') {
                $domains[] = 'www.' . $domain;
            }
        }
        return $domains;
    }

    /**
     * Extract domain name from URL.
     *
     * @param $url
     * @return string|null
     */
    private function extractDomainFromUrl($url): ?string
    {
        $parsedUrl = parse_url($url);
        if (array_key_exists('host', $parsedUrl) && !empty($parsedUrl['host'])) {
            return $parsedUrl['host'];
        }
        return null;
    }
}
