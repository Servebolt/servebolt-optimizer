<?php

namespace Servebolt\Optimizer\Admin\CachePurgeControl\Ajax;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Admin\SharedAjaxMethods;
use Servebolt\Optimizer\Sdk\Cloudflare\Cloudflare as CloudflareSdk;
use function Servebolt\Optimizer\Helpers\arrayGet;
use function Servebolt\Optimizer\Helpers\ajaxUserAllowed;
use Exception;

/**
 * Class Configuration
 * @package Servebolt\Optimizer\Admin\CachePurgeControl\Ajax
 */
class Configuration extends SharedAjaxMethods
{

    /**
     * CachePurgeConfiguration constructor.
     */
    public function __construct()
    {
        add_action('wp_ajax_servebolt_lookup_zones', [$this, 'lookupZonesCallback']);
        add_action('wp_ajax_servebolt_lookup_zone', [$this, 'lookupZoneCallback']);
        add_action('wp_ajax_servebolt_validate_cf_settings_form', [$this, 'validateCfSettingsFormCallback']);
    }

    /**
     * Try to fetch available zones based on given API credentials.
     */
    public function lookupZonesCallback(): void
    {
        $this->checkAjaxReferer();
        ajaxUserAllowed();

        $authType = sanitize_text_field($_POST['auth_type']);
        $credentials = arrayGet('credentials', $_POST);
        try {
            $cfSdk = new CloudflareSdk(compact('authType', 'credentials'));
            $zones = $cfSdk->listZones();
            if ( ! empty($zones) ) {
                wp_send_json_success([
                    'markup' => $this->generateZoneListMarkup($zones),
                ]);
                return;
            }
            throw new Exception;
        } catch (Exception $e) {
            wp_send_json_error();
        }
    }

    /**
     * Try to resolve the zone name by zone ID.
     */
    public function lookupZoneCallback(): void
    {
        $this->checkAjaxReferer();
        ajaxUserAllowed();

        parse_str($_POST['form'], $formData);
        $authType = sanitize_text_field(arrayGet('servebolt_cf_auth_type', $formData));
        $apiToken = sanitize_text_field(arrayGet('servebolt_cf_api_token', $formData));
        $email = sanitize_text_field(arrayGet('servebolt_cf_email', $formData));
        $apiKey = sanitize_text_field(arrayGet('servebolt_cf_api_key', $formData));
        $zoneId = sanitize_text_field(arrayGet('servebolt_cf_zone_id', $formData));

        if (!$authType || !$zoneId) {
            wp_send_json_error();
        }

        try {
            switch ($authType) {
                case 'api_token':
                    if (!$apiToken) {
                        wp_send_json_error();
                    }
                    $cfSdk = new CloudflareSdk([
                        'authType' => 'api_token',
                        'credentials' => compact('apiToken'),
                    ]);
                    break;
                case 'api_key':
                    if (!$email || $apiKey) {
                        wp_send_json_error();
                    }
                    $cfSdk = new CloudflareSdk([
                        'authType' => 'api_key',
                        'credentials' => compact('email', 'apiKey'),
                    ]);
                    break;
                default:
                    throw new Exception;
            }
            $zone = $cfSdk->getZoneById($zoneId);
            if ($zone && isset($zone->name)) {
                wp_send_json_success([
                    'zone' => $zone->name,
                ]);
            } else {
                throw new Exception;
            }
        } catch (Exception $e) {
            wp_send_json_error();
        }
    }

    /**
     * Validate Cloudflare settings form.
     */
    public function validateCfSettingsFormCallback(): void
    {
        $this->checkAjaxReferer();
        ajaxUserAllowed();

        parse_str($_POST['form'], $formData);
        $errors = [];

        $featureIsActive = arrayGet('servebolt_cache_purge_switch', $formData) === true;
        $cfIsActive = arrayGet('servebolt_cache_purge_driver', $formData) == 'cloudflare';
        $authType = sanitize_text_field(arrayGet('servebolt_cf_auth_type', $formData));
        $apiToken = sanitize_text_field(arrayGet('servebolt_cf_api_token', $formData));
        $email = sanitize_text_field(arrayGet('servebolt_cf_email', $formData));
        $apiKey = sanitize_text_field(arrayGet('servebolt_cf_api_key', $formData));
        $zoneId = sanitize_text_field(arrayGet('servebolt_cf_zone_id', $formData));
        $shouldCheckZone = false;

        if (!$featureIsActive || !$cfIsActive) {
            wp_send_json_success();
        }

        if (!$authType) {
            wp_send_json_error();
        }

        switch ($authType) {
            case 'api_token':
                if (empty($apiToken)) {
                    $errors['api_token'] = __('You need to provide an API token.', 'servebolt-wp');
                } else {
                    $cfSdk = new CloudflareSdk([
                        'authType' => 'api_token',
                        'credentials' => compact('apiToken')
                    ]);
                    try {
                        if (!$cfSdk->verifyApiToken()) {
                            throw new Exception;
                        }
                        $shouldCheckZone = true;
                    } catch (Exception $e) {
                        $errors['api_token'] = __('Invalid API token.', 'servebolt-wp');
                    }
                }
                break;
            case 'api_key':
                if (empty($email)) {
                    $errors['email'] = __('You need to provide an email address.', 'servebolt-wp');
                }

                if (empty($apiKey)) {
                    $errors['api_key'] = __('You need to provide an API key.', 'servebolt-wp');
                }

                if (!empty($email) && !empty($apiKey)) {
                    $cfSdk = new CloudflareSdk([
                        'authType' => 'api_key',
                        'credentials' => compact('email', 'apiKey')
                    ]);
                    try {
                        if ( ! $cfSdk->verifyUser() ) {
                            throw new Exception;
                        }
                        $shouldCheckZone = true;
                    } catch (Exception $e) {
                        $errors['api_key_credentials'] = __('Invalid API credentials.', 'servebolt-wp');
                    }
                }

                break;
            default:
                $errors[] = __('Invalid authentication type.', 'servebolt-wp');
        }

        if ($shouldCheckZone) {
            if (empty($zoneId)) {
                $errors['zone_id'] = __('You need to provide a zone.', 'servebolt-wp');
            } else {
                try {
                    if (!$zoneId = $cfSdk->getZoneById($zoneId)) {
                        throw new Exception;
                    }
                } catch (Exception $e) {
                    $errors['zone_id'] = __('Seems like we are lacking access to zone (check permissions) or the zone does not exist.');
                }
            }
        } else {
            /*
            $string = $authType == 'api_token' ? 'token' : 'credentials';
            $errors[] = sprintf(__('Cannot validate zone due to insufficient/invalid API %s', 'servebolt-wp'), $string);
            */
        }

        if (empty($errors)) {
            wp_send_json_success();
        } else {
            wp_send_json_error([
                'errors' => $errors,
                'error_html' => $this->generateFormErrorHtml($errors),
            ]);
        }
    }

    /**
     * Generate li-markup of zones-array.
     *
     * @param array $zones
     *
     * @return string
     */
    private function generateZoneListMarkup(array $zones): string
    {
        $markup = '';
        foreach($zones as $zone) {
            $markup .= sprintf('<li><a href="#" data-name="%s" data-id="%s">%s (%s)</a></li>', esc_attr($zone->name), esc_attr($zone->id), $zone->name, $zone->id);
        }
        return $markup;
    }

    /**
     * Generate markup for form validation errors.
     *
     * @param $errors
     *
     * @return string
     */
    private function generateFormErrorHtml($errors): string
    {
        $errors = array_map(function ($error) {
            return rtrim(trim($error), '.');
        }, $errors);
        return '<br><strong>' . __('Validation errors:', 'servebolt-wp') . '</strong><ul><li>- ' . implode('</li><li>- ', $errors) . '</li></ul>';
    }
}
