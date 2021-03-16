<?php

namespace Servebolt\Optimizer\Admin\Ajax\CachePurge;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Servebolt\Optimizer\Admin\Ajax\SharedMethods;
use Servebolt\Optimizer\Sdk\Cloudflare\Cloudflare as CloudflareSdk;
use Exception;

class Configuration extends SharedMethods
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
        sb_ajax_user_allowed();

        $authType = sanitize_text_field($_POST['auth_type']);
        $credentials = sb_array_get('credentials', $_POST);
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
        sb_ajax_user_allowed();

        parse_str($_POST['form'], $form_data);
        $authType = sanitize_text_field($form_data['servebolt_cf_auth_type']);
        $apiToken = sanitize_text_field($form_data['servebolt_cf_api_token']);
        $email = sanitize_text_field($form_data['servebolt_cf_email']);
        $apiKey = sanitize_text_field($form_data['servebolt_cf_api_key']);
        $zoneId = sanitize_text_field($form_data['servebolt_cf_zone_id']);
        try {
            switch ($authType) {
                case 'api_token':
                    $cfSdk = new CloudflareSdk([
                        'authType' => 'api_token',
                        'credentials' => compact('apiToken'),
                    ]);
                    break;
                case 'api_key':
                    $cfSdk = new CloudflareSdk([
                        'authType' => 'api_key',
                        'credentials' => compact('email', 'apiKey'),
                    ]);
                    break;
                default:
                    throw new Exception;
            }
            $zone = $cfSdk->getZoneById($zoneId);
            if ( $zone && isset($zone->name) ) {
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
        sb_ajax_user_allowed();

        parse_str($_POST['form'], $form_data);
        $errors = [];

        $featureIsActive = array_key_exists('cache_purge_switch', $form_data)
            && filter_var($form_data['cache_purge_switch'], FILTER_VALIDATE_BOOLEAN) === true;
        $cfIsActive = array_key_exists('cache_purge_driver', $form_data)
            && $form_data['cache_purge_driver'] == 'cloudflare';
        $authType = sanitize_text_field($form_data['servebolt_cf_auth_type']);
        $apiToken = sanitize_text_field($form_data['servebolt_cf_api_token']);
        $email = sanitize_text_field($form_data['servebolt_cf_email']);
        $apiKey = sanitize_text_field($form_data['servebolt_cf_api_key']);
        $zoneId = sanitize_text_field($form_data['servebolt_cf_zone_id']);
        $shouldCheckZone = false;

        if (!$featureIsActive || !$cfIsActive) {
            wp_send_json_success();
        }

        switch ($authType) {
            case 'api_token':
                if ( empty($apiToken) ) {
                    $errors['api_token'] = sb__('You need to provide an API token.');
                } else {
                    $cfSdk = new CloudflareSdk([
                        'authType' => 'api_token',
                        'credentials' => compact('apiToken')
                    ]);
                    try {
                        if ( ! $cfSdk->verifyToken() ) {
                            throw new Exception;
                        }
                        $shouldCheckZone = true;
                    } catch (Exception $e) {
                        $errors['api_token'] = sb__('Invalid API token.');
                    }
                }
                break;
            case 'api_key':
                if ( empty($email) ) {
                    $errors['email'] = sb__('You need to provide an email address.');
                }

                if ( empty($apiKey) ) {
                    $errors['api_key'] = sb__('You need to provide an API key.');
                }

                if ( ! empty($email) && ! empty($apiKey) ) {
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
                        $errors['api_key_credentials'] = sb__( 'Invalid API credentials.' );
                    }
                }

                break;
            default:
                $errors[] = sb__('Invalid authentication type.');
        }

        if ( $shouldCheckZone ) {
            if ( empty($zoneId) ) {
                $errors['zone_id'] = sb__('You need to provide a zone.');
            } else {
                try {
                    if ( ! $zoneId = $cfSdk->getZoneById($zoneId) ) {
                        throw new Exception;
                    }
                } catch (Exception $e) {
                    $errors['zone_id'] = sb__('Seems like we are lacking access to zone (check permissions) or the zone does not exist.');
                }
            }
        } else {
            /*
            $string = $authType == 'api_token' ? 'token' : 'credentials';
            $errors[] = sprintf(sb__('Cannot validate zone due to insufficient/invalid API %s'), $string);
            */
        }

        if ( empty($errors) ) {
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
        return '<br><strong>' . sb__('Validation errors:') . '</strong><ul><li>- ' . implode('</li><li>- ', $errors) . '</li></ul>';
    }
}
