<?php

namespace Servebolt\Optimizer\Cli;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;

/**
 * Class CliHelpers
 * @package Servebolt\Optimizer\Cli
 */
class CliHelpers
{

    /**
     * @var bool Whether to return JSON in CLI.
     */
    private static $returnJson = false;

    /**
     * Set initial JSON return state.
     */
    public static function setReturnJsonInitState(): void
    {
        $instance = \Servebolt\Optimizer\Admin\GeneralSettings\GeneralSettings::getInstance();
        self::$returnJson = $instance->returnJsonInCli();
    }

    /**
     * Whether to return JSON in CLI.
     *
     * @return bool
     */
    public static function returnJson(): bool
    {
        return self::$returnJson;
    }

    /**
     * Check if we should affect all sites in multisite-network.
     *
     * @param array $assocArgs
     *
     * @return bool
     */
    public static function affectAllSites(array $assocArgs): bool
    {
        return is_multisite() && array_key_exists('all', $assocArgs);
    }

    /**
     * Display CLI separator output.
     *
     * @param int $length
     */
    public static function separator($length = 20): void
    {
        WP_CLI::line(str_repeat('-', $length));
    }

    /**
     * Handle CLI user input.
     *
     * @param bool $validationClosure
     *
     * @return string
     */
    public static function userInput($validationClosure = false)
    {
        $handle = fopen ('php://stdin', 'r');
        $response = trim(fgets($handle));
        fclose($handle);
        if ( is_callable($validationClosure) ) {
            return $validationClosure($response);
        }
        return $response;
    }

    /**
     * Collect parameter interactively via CLI prompt.
     *
     * @param $promptMessage
     * @param $errorMessage
     * @param bool $validation
     * @param bool|callable $beforeInputPrompt
     * @param bool $quitOnFail
     *
     * @return string
     */
    public static function collectParameter($promptMessage, $errorMessage, bool $validation = false, $beforeInputPrompt = false, bool $quitOnFail = false)
    {

        // Determine validation
        $defaultValidation = function($input) {
            if (empty($input)) {
                return false;
            }
            return $input;
        };
        $validation = (is_callable($validation) ? $validation : $defaultValidation);

        $failCount = 1;
        $maxFailCount = 5;
        set_param:

        // Call before prompt-function
        if (is_callable($beforeInputPrompt)) {
            $beforeInputPrompt();
        }

        if ($failCount == $maxFailCount) {
            echo '[Last attempt] ';
        }

        echo $promptMessage;
        $param = self::userInput($validation);
        if (!$param) {
            if ($failCount >= $maxFailCount) {
                WP_CLI::error('No input received, exiting.');
            }
            $failCount++;
            WP_CLI::error($errorMessage, $quitOnFail);
            goto set_param;
        }
        return $param;
    }

    /**
     * A confirm-prompt that is customizable and does not necessarily quit after you reply no (unlike WP-CLI's own confirm-prompt...).
     * @param $text
     *
     * @return mixed
     */
    public static function confirm($text)
    {
        $result = self::collectParameter($text . ' ' . __('[y/n]', 'servebolt-wp') . ' ', __('Please reply with either "y" or "n".', 'servebolt-wp'), function($input) {
            switch ($input) {
                case 'y':
                    return ['boolean' => true];
                    break;
                case 'n':
                    return ['boolean' => false];
                    break;
            }
            return false;
        });
        return $result['boolean'];
    }
}
