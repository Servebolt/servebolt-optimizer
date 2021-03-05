<?php

namespace Servebolt\Optimizer\Admin\Ajax\CachePurge;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Servebolt\Optimizer\Admin\Ajax\SharedMethods;

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
    public function lookupZonesCallback()
    {
        $this->checkAjaxReferer();
        sb_ajax_user_allowed();

        $auth_type = sanitize_text_field($_POST['auth_type']);
        $credentials = sb_array_get('credentials', $_POST);
        try {
            sb_cf_cache()->cf()->set_credentials($auth_type, $credentials);
            $zones = sb_cf_cache()->cf()->list_zones();
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
    public function lookupZoneCallback() {
        $this->checkAjaxReferer();
        sb_ajax_user_allowed();

        parse_str($_POST['form'], $form_data);
        $auth_type = sanitize_text_field($form_data['servebolt_cf_auth_type']);
        $api_token = sanitize_text_field($form_data['servebolt_cf_api_token']);
        $email     = sanitize_text_field($form_data['servebolt_cf_email']);
        $api_key   = sanitize_text_field($form_data['servebolt_cf_api_key']);
        $zone_id   = sanitize_text_field($form_data['servebolt_cf_zone_id']);
        try {
            switch ($auth_type) {
                case 'api_token':
                    sb_cf_cache()->cf()->set_credentials('api_token', compact('api_token'));
                    break;
                case 'api_key':
                    sb_cf_cache()->cf()->set_credentials('api_key', compact('email', 'api_key'));
                    break;
                default:
                    throw new Exception;
            }
            $zone = sb_cf_cache()->get_zone_by_id($zone_id);
            if ( $zone && isset($zone->name) ) {
                return wp_send_json_success([
                    'zone' => $zone->name,
                ]);
            }
            throw new Exception;
        } catch (Exception $e) {
            wp_send_json_error();
        }
    }

    /**
     * Validate Cloudflare settings form.
     */
    public function validateCfSettingsFormCallback() {
        $this->checkAjaxReferer();
        sb_ajax_user_allowed();

        parse_str($_POST['form'], $form_data);
        $errors = [];

        $is_active = array_key_exists('servebolt_cf_switch', $form_data) && filter_var($form_data['servebolt_cf_switch'], FILTER_VALIDATE_BOOLEAN) === true;
        $auth_type = sanitize_text_field($form_data['servebolt_cf_auth_type']);
        $api_token = sanitize_text_field($form_data['servebolt_cf_api_token']);
        $email     = sanitize_text_field($form_data['servebolt_cf_email']);
        $api_key   = sanitize_text_field($form_data['servebolt_cf_api_key']);
        $zone_id   = sanitize_text_field($form_data['servebolt_cf_zone_id']);
        $validate_zone = false;

        if ( ! $is_active ) {
            wp_send_json_success();
        }

        switch ($auth_type) {
            case 'api_token':
                if ( empty($api_token) ) {
                    $errors['api_token'] = sb__('You need to provide an API token.');
                } else {
                    sb_cf_cache()->cf()->set_credentials('api_token', compact('api_token'));
                    try {
                        if ( ! sb_cf_cache()->cf()->verify_token() ) {
                            throw new Exception;
                        }
                        $validate_zone = true;
                    } catch (Exception $e) {
                        $errors['api_token'] = sb__('Invalid API token.');
                    }
                }
                break;
            case 'api_key':
                if ( empty($email) ) {
                    $errors['email'] = sb__('You need to provide an email address.');
                }

                if ( empty($api_key) ) {
                    $errors['api_key'] = sb__('You need to provide an API key.');
                }

                if ( ! empty($email) && ! empty($api_key) ) {
                    sb_cf_cache()->cf()->set_credentials('api_key', compact('email', 'api_key'));
                    try {
                        if ( ! sb_cf_cache()->cf()->verify_user() ) {
                            throw new Exception;
                        }
                        $validate_zone = true;
                    } catch (Exception $e) {
                        $errors['api_key_credentials'] = sb__( 'Invalid API credentials.' );
                    }
                }

                break;
            default:
                $errors[] = sb__('Invalid authentication type.');

        }

        if ( $validate_zone ) {
            if ( empty($zone_id) ) {
                $errors['zone_id'] = sb__('You need to provide a zone.');
            } else {
                try {
                    if ( ! $zone_id = sb_cf_cache()->cf()->get_zone_by_id($zone_id) ) {
                        throw new Exception;
                    }
                } catch (Exception $e) {
                    $errors['zone_id'] = sb__('Seems like we are lacking access to zone (check permissions) or the zone does not exist.');
                }
            }
        } else {
            /*
            $string = $auth_type == 'api_token' ? 'token' : 'credentials';
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
    private function generateZoneListMarkup(array $zones) : string
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
    private function generateFormErrorHtml($errors) {
        $errors = array_map(function ($error) {
            return rtrim(trim($error), '.');
        }, $errors);
        return '<br><strong>' . sb__('Validation errors:') . '</strong><ul><li>- ' . implode('</li><li>- ', $errors) . '</li></ul>';
    }
}
