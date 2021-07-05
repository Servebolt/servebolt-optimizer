<?php

namespace Servebolt\Optimizer\TextDomainLoader;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

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
    public static function aFasterLoadTextdomain(bool $returnValue, string $domain, string $moFile): bool
    {
        global $l10n;

        if (!is_readable($moFile)) {
            return false;
        }

        $data = get_transient(md5($moFile));
        $mtime = filemtime($moFile);

        $mo = new MO();
        if (!$data || !isset($data['mtime']) || $mtime > $data['mtime']) {
            if (!$mo->import_from_file($moFile)) {
                return false;
            }
            $data = [
                'mtime' => $mtime,
                'entries' => $mo->entries,
                'headers' => $mo->headers
            ];
            set_transient(md5($moFile), $data);
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
}
