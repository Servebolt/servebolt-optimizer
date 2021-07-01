<?php

namespace Servebolt\Optimizer\Admin\AcceleratedDomainsImageControl\Ajax;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\AcceleratedDomains\ImageResize\ImageSizeIndexModel;
use Servebolt\Optimizer\Admin\SharedAjaxMethods;
use function Servebolt\Optimizer\Helpers\ajaxUserAllowed;
use function Servebolt\Optimizer\Helpers\arrayGet;
use function Servebolt\Optimizer\Helpers\view;

/**
 * Class ImageSizeIndex
 * @package Servebolt\Optimizer\Admin\AcceleratedDomainsImageControl\Ajax
 */
class ImageSizeIndex extends SharedAjaxMethods
{
    /**
     * ImageSizeIndex constructor.
     */
    public function __construct()
    {
        add_action('wp_ajax_servebolt_acd_load_image_sizes', [$this, 'getImageSizes']);
        add_action('wp_ajax_servebolt_acd_add_image_size', [$this, 'addImageSize']);
        add_action('wp_ajax_servebolt_acd_remove_image_size', [$this, 'removeImageSize']);
        add_action('wp_ajax_servebolt_acd_remove_image_sizes', [$this, 'removeImageSizes']);
    }

    /**
     * AJAX handling to return image size list.
     */
    public function getImageSizes(): void
    {
        $this->checkAjaxReferer();
        ajaxUserAllowed();
        $extraSizes = ImageSizeIndexModel::getSizes();
        wp_send_json_success([
            'markup' => view('accelerated-domains.image-resize.image-size-index-list', compact('extraSizes'), false)
        ]);
    }

    /**
     * AJAX handling to add an image size.
     */
    public function addImageSize(): void
    {
        $this->checkAjaxReferer();
        ajaxUserAllowed();
        $value = sanitize_text_field(arrayGet('value', $_POST));
        if ($matches = ImageSizeIndexModel::validateValue($value)) {
            if ($matches[1] <= 0) {
                wp_send_json_error(['message' => __('Value must be above 0.', 'servebolt-wp')]);
            } elseif (ImageSizeIndexModel::sizeExists($matches[1], $matches[2])) {
                wp_send_json_error(['message' => __('Size already exists.', 'servebolt-wp')]);
            } else {
                ImageSizeIndexModel::addSize($matches[1], $matches[2]);
                wp_send_json_success();
            }
        } else {
            wp_send_json_error();
        }
    }

    /**
     * AJAX handling to remove an image size.
     */
    public function removeImageSize(): void
    {
        $this->checkAjaxReferer();
        ajaxUserAllowed();
        $value = sanitize_text_field(arrayGet('value', $_POST));
        if ($matches = ImageSizeIndexModel::validateValue($value)) {
            if (!ImageSizeIndexModel::sizeExists($matches[1], $matches[2])) {
                wp_send_json_error(['message' => __('Size does not exist.', 'servebolt-wp')]);
            } else {
                ImageSizeIndexModel::removeSize($matches[1], $matches[2]);
                wp_send_json_success();
            }
        } else {
            wp_send_json_error();
        }
    }

    /**
     * AJAX handling to remove multiple image sizes.
     */
    public function removeImageSizes(): void
    {
        $this->checkAjaxReferer();
        ajaxUserAllowed();
        $sizesToRemove = array_map('trim', explode(',', sanitize_text_field(arrayGet('value', $_POST))));
        foreach($sizesToRemove as $value) {
            if ($matches = ImageSizeIndexModel::validateValue($value)) {
                if (ImageSizeIndexModel::sizeExists($matches[1], $matches[2])) {
                    ImageSizeIndexModel::removeSize($matches[1], $matches[2]);
                }
            }
        }
        wp_send_json_success();
    }
}
