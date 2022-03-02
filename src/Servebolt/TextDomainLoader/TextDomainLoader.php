<?php

namespace Servebolt\Optimizer\TextDomainLoader;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use MO;

/**
 * Class TextDomainLoader
 * @package Servebolt\Optimizer\TextDomainLoader
 */
class TextDomainLoader
{
    /**
     * Override the text domain loader.
     *
     * @param bool $returnValue
     * @param string $domain
     * @param string $moFile
     * @return bool
     */
    public static function aFasterLoadTextDomain(bool $returnValue, string $domain, string $moFile): bool
    {
        global $l10n;

        if (!is_readable($moFile)) {
            return false;
        }

        $moFileHash = md5($moFile);
        $transientKey = 'sb-optimizer-text-domain-loader-' . $moFileHash;
        $data = get_transient($transientKey);
        $mtime = filemtime($moFile);

        $mo = new MO();
        if (!$data || !isset($data['mtime']) || $mtime > $data['mtime']) {
            self::maybeCleanupLegacyTransient($moFileHash);
            if (!$mo->import_from_file($moFile)) {
                return false;
            }
            $data = [
                'mtime' => $mtime,
                'entries' => $mo->entries,
                'headers' => $mo->headers
            ];
            set_transient($transientKey, $data, YEAR_IN_SECONDS);
        } else {
            $mo->entries = $data['entries'];
            $mo->headers = $data['headers'];
        }

        if (isset($l10n[$domain])) {
            $mo->merge_with($l10n[$domain]);
        }

        $l10n[$domain] = &$mo;

        return true;
    }

    /**
     * Delete legacy transient (transients set before we added our own prefix to it, which we should have done from the get-go).
     *
     * @param $transientKey
     * @return void
     */
    private static function maybeCleanupLegacyTransient($transientKey)
    {
        delete_transient($transientKey);
    }
}
