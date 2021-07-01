<?php

namespace Servebolt\Optimizer\Cli\AcceleratedDomains;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\AcceleratedDomains\ImageResize\ImageSizeIndexModel;
use WP_CLI;
use function WP_CLI\Utils\format_items as WP_CLI_FormatItems;
use Servebolt\Optimizer\AcceleratedDomains\ImageResize\AcceleratedDomainsImageResize as AcceleratedDomainsImageResizeClass;
use Servebolt\Optimizer\Cli\CliHelpers;
use function Servebolt\Optimizer\Helpers\booleanToStateString;
use function Servebolt\Optimizer\Helpers\iterateSites;



/**
 * Class AcceleratedDomainsImageResize
 * @package Servebolt\Optimizer\Cli\AcceleratedDomains
 */
class AcceleratedDomainsImageSizeIndex
{

    /**
     * AcceleratedDomainsImageResize constructor.
     */
    public function __construct()
    {
        WP_CLI::add_command('servebolt acd image-sizes list', [$this, 'listSizes']);
        WP_CLI::add_command('servebolt acd image-sizes add', [$this, 'addSize']);
        WP_CLI::add_command('servebolt acd image-sizes delete', [$this, 'deleteSize']);
    }

    /**
     * Lists sizes.
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Return format.
     * ---
     * default: text
     * options:
     *   - text
     *   - json
     * ---
     *
     * ## EXAMPLES
     *
     *      # Lists all registered sizes.
     *      wp servebolt acd image-sizes list
     *
     *      # Lists all registered sizes in JSON-format.
     *      wp servebolt acd image-sizes list --format=json
     */
    public function listSizes($args, $assocArgs): void
    {
        CliHelpers::setReturnJson($assocArgs);
        $sizes = ImageSizeIndexModel::getSizes();
        if (empty($sizes)) {
            $message = __('No sizes.', 'servebolt-wp');
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson([
                    'success' => false,
                    'message' => $message,
                ]);
            } else {
                WP_CLI::warning($message);
            }
        } else {
            $sizes = array_map(function($item){
                return ['Size' => $item['value'] . $item['descriptor'] ];
            }, $sizes);
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson($sizes);
            } else {
                WP_CLI_FormatItems('table', $sizes, array_keys(current($sizes)));
            }
        }
    }

    /**
     * Adds a size to be used in the srcset-attribute for images.
     *
     * ## OPTIONS
     *
     * <Value>
     * : Numeric value (max. 9999) with a descriptor-suffix which defines whether the value is width or height.
     *
     * [--format=<format>]
     * : Return format.
     * ---
     * default: text
     * options:
     *   - text
     *   - json
     * ---
     *
     * ## EXAMPLES
     *
     *      # Add an image size with 1200px width.
     *      wp servebolt acd image-sizes add 1200w
     *
     *      # Add an image size with 1600px height and return JSON-format.
     *      wp servebolt acd image-sizes add 1200w --format=json
     */
    public function addSize($args, $assocArgs): void
    {
        CliHelpers::setReturnJson($assocArgs);
        list($rawValue) = $args;
        if ($matches = ImageSizeIndexModel::validateValue($rawValue)) {
            $this->invalidFormatMessage();
            return;
        }
        list($original, $value, $descriptor) = $matches;
        if (ImageSizeIndexModel::sizeExists($value, $descriptor)) {
            $message = __('Size already exists.', 'servebolt-wp');
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson([
                    'success' => false,
                    'message' => $message,
                ]);
            } else {
                WP_CLI::warning($message);
            }
        } else if (ImageSizeIndexModel::addSize($value, $descriptor)) {
            $message = __('Size added.', 'servebolt-wp');
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson([
                    'success' => true,
                    'message' => $message,
                ]);
            } else {
                WP_CLI::success($message);
            }
        } else {
            $message = __('Could not add size.', 'servebolt-wp');
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson([
                    'success' => false,
                    'message' => $message,
                ]);
            } else {
                WP_CLI::error($message);
            }
        }
    }

    /**
     * Delete a size.
     *
     * ## OPTIONS
     *
     * <Value>
     * : Numeric value (max. 9999) with a descriptor-suffix which defines whether the value is width or height.
     *
     * [--format=<format>]
     * : Return format.
     * ---
     * default: text
     * options:
     *   - text
     *   - json
     * ---
     *
     * ## EXAMPLES
     *
     *      # Delete an image size with 1200px width.
     *      wp servebolt acd image-sizes delete 1200w
     *
     *      # Delete an image size with 1600px height and return JSON-format.
     *      wp servebolt acd image-sizes delete 1200w --format=json
     */
    public function deleteSize($args, $assocArgs): void
    {
        CliHelpers::setReturnJson($assocArgs);
        list($rawValue) = $args;
        if ($matches = ImageSizeIndexModel::validateValue($rawValue)) {
            $this->invalidFormatMessage();
            return;
        }
        list($original, $value, $descriptor) = $matches;
        if (!ImageSizeIndexModel::sizeExists($value, $descriptor)) {
            $message = __('Size does not exist.', 'servebolt-wp');
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson([
                    'success' => false,
                    'message' => $message,
                ]);
            } else {
                WP_CLI::warning($message);
            }
        } else if (ImageSizeIndexModel::removeSize($value, $descriptor)) {
            $message = __('Size deleted.', 'servebolt-wp');
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson([
                    'success' => true,
                    'message' => $message,
                ]);
            } else {
                WP_CLI::success($message);
            }
        } else {
            $message = __('Could not delete size.', 'servebolt-wp');
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson([
                    'success' => false,
                    'message' => $message,
                ]);
            } else {
                WP_CLI::error($message);
            }
        }
    }

    /**
     * Display error message about invalid input format.
     */
    private function invalidFormatMessage(): void
    {
        $message = __('Invalid format. Example: 1200w', 'servebolt-wp');
        if (CliHelpers::returnJson()) {
            CliHelpers::printJson([
                'success' => false,
                'message' => $message,
            ]);
        } else {
            WP_CLI::error($message);
        }
    }
}
