<?php

namespace Servebolt\Optimizer\CachePurge\Drivers;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Servebolt\Optimizer\Api\Servebolt\Servebolt as ServeboltApi;
use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeInterface;

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
     * @param string $url
     * @return mixed
     */
    public function purgeByUrl(string $url)
    {
        return $this->apiInstance->environment()->purgeCache([$url]);
    }

    /**
     * @param array $urls
     * @return mixed
     */
    public function purgeByUrls(array $urls)
    {
        return $this->apiInstance->environment->purgeCache(
            $this->apiInstance->getEnvironmentId(),
            $urls
        );
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
     * @return mixed
     */
    public function purgeAll()
    {
        return $this->apiInstance->environment->purgeCache(
            $this->apiInstance->getEnvironmentId(),
            [],
            $this->getPurgeAllPrefixes()
        );
    }

    /**
     * Build array of prefix URLs when purging all cache for a site.
     *
     * @return array
     */
    public function getPurgeAllPrefixes(): array
    {

        $override = apply_filters('sb_optimizer_acd_purge_all_prefixes_early_override', false); // Let user skip the method and deal with it themselves
        if (is_array($override)) {
            return $override;
        }

        $prefixes = [];
        if (is_multisite()) {
            $prefixes = $this->getDomainsWithThirdPartySupport(); // All domains in a multisite-setup
        } elseif ($domain = $this->extractDomainFromUrl(get_site_url())) {
            $prefixes = [$domain]; // Single site domain
        }

        if (apply_filters('sb_optimizer_add_www_domains_on_acd_purge_all', true)) {
            $prefixes = $this->addWwwDomains($prefixes);
        }

        return apply_filters('sb_optimizer_acd_purge_all_prefixes', $prefixes);
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
