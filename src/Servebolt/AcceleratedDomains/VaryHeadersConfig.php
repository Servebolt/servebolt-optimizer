<?php

namespace Servebolt\Optimizer\AcceleratedDomains;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\AcceleratedDomains\AcceleratedDomains;
use Servebolt\Optimizer\Api\Servebolt\Servebolt as ServeboltApi;
use Servebolt\Optimizer\CachePurge\CachePurge;
use Servebolt\Optimizer\Traits\Singleton;
use Throwable;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\getOptionName;
use function Servebolt\Optimizer\Helpers\listenForCheckboxOptionChange;
use function Servebolt\Optimizer\Helpers\listenForOptionChange;
use function Servebolt\Optimizer\Helpers\setDefaultOption;
use function Servebolt\Optimizer\Helpers\getDomainNameOfWebSite;

/**
 * Handles storage and sanitization for Accelerated Domains vary headers.
 */
class VaryHeadersConfig
{
    use Singleton;

    private const OPTION_KEY = 'acd_vary_headers';
    private const DEFAULT_SELECTION = [];

    /**
     * Map of allowed keys to HTTP header names.
     */
    private const HEADER_MAP = [
        'br' => 'User-Agent',
        'lang' => 'Accept-Language',
        'co' => 'X-Origin-Country',
    ];

    public static function init(): void
    {
        self::getInstance();
    }

    public function __construct()
    {
        setDefaultOption(self::OPTION_KEY, self::defaultSelection());

        add_filter('servebolt_get_option_' . getOptionName(self::OPTION_KEY), function ($value) {
            return is_array($value) ? $value : [];
        });

        listenForOptionChange(self::OPTION_KEY, function ($newValue) {
            $this->sync($newValue);
        });

        listenForCheckboxOptionChange('acd_switch', function ($wasActive, $isActive) {
            if ($isActive) {
                $this->sync();
            }
        });
    }

    /**
     * Option key used to store selections.
     */
    public static function optionKey(): string
    {
        return self::OPTION_KEY;
    }

    /**
     * Default vary header selection.
     * All vary headers are disabled by default.
     */
    public static function defaultSelection(): array
    {
        return self::DEFAULT_SELECTION;
    }

    /**
     * Allowed vary header keys mapped to header names.
     */
    public static function availableHeaders(): array
    {
        return self::HEADER_MAP;
    }

    /**
     * Current sanitized selection from options.
     */
    public static function selection(): array
    {
        return self::sanitizeSelection(getOption(self::OPTION_KEY, self::defaultSelection()));
    }

    /**
     * Sanitize a selection of vary header keys.
     */
    public static function sanitizeSelection($value): array
    {
        if (!is_array($value)) {
            return [];
        }
        $value = array_map(function ($item) {
            return strtolower(trim((string) $item));
        }, $value);
        $value = array_values(array_intersect($value, array_keys(self::HEADER_MAP)));
        return array_unique($value);
    }

    /**
     * Sync selection to Servebolt API.
     *
     * @param mixed|null $selection
     */
    public function sync($selection = null): void
    {
        if (!AcceleratedDomains::isActive()) {
            return;
        }

        $selectedHeaders = self::sanitizeSelection(
            is_null($selection) ? self::selection() : $selection
        );

        if (empty($selectedHeaders)) {
            return;
        }

        $payload = $this->buildPayload($selectedHeaders);
        if (is_null($payload)) {
            return;
        }

        try {
            $serveboltApi = ServeboltApi::getInstance();
            if (!$serveboltApi->isConfigured()) {
                return;
            }

            $response = $serveboltApi->environment->setVaryHeaders(
                $serveboltApi->getEnvironmentId(),
                $payload
            );

            if (!$response->wasSuccessful()) {
                error_log('[Servebolt Optimizer] Failed to update Accelerated Domains vary headers.');
            }
        } catch (Throwable $e) {
            error_log(sprintf('[Servebolt Optimizer] Error updating vary headers: %s', $e->getMessage()));
        }
    }


    /**
     * Build the payload for the API request.
     *
     * @param array $selectedHeaders
     * @return array|null
     */
    private function buildPayload(array $selectedHeaders): ?array
    {
        $varyHeaders = $this->buildApiValue($selectedHeaders);
        $domain = getDomainNameOfWebSite();
        $zone = $this->getZone();

        if (empty($varyHeaders) || empty($domain) || empty($zone)) {
            return null;
        }

        return [
            'vary_headers' => $varyHeaders,
            'domain' => $domain,
            'zone' => $zone,
        ];
    }

    /**
     * Transform the selection to the compact value expected by the API.
     *
     * @param array $selectedHeaders
     * @return string
     */
    private function buildApiValue(array $selectedHeaders): string
    {
        return implode(',', self::sanitizeSelection($selectedHeaders));
    }

    /**
     * Determine which zone should be sent to the API.
     *
     * @return string|null
     */
    private function getZone(): ?string
    {
        $driver = CachePurge::resolveDriverNameWithoutConfigCheck();
        if ($driver === 'acd') {
            return 'acd';
        }
        if ($driver === 'serveboltcdn') {
            return 'sbcdn';
        }
        return null;
    }
}
