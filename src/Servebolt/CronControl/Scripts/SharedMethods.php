<?php

namespace Servebolt\Optimizer\CronControl\Scripts;

use Servebolt\Optimizer\Utils\EnvFile\Reader as EnvFileReader;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

trait SharedMethods
{
    /**
     * Get the file content and populate variables.
     *
     * @return string
     */
    public function getFileContent(): ?string
    {
        return $this->populateVariables(
            $this->getFileRawContent()
        );
    }

    /**
     * Get the path to the root WP directory.
     *
     * @return mixed
     */
    private function getWpPath()
    {
        $env = EnvFileReader::getInstance();
        return $env->public_dir;
    }

    /**
     * Populate script variables.
     *
     * @param $fileContent
     * @return string
     */
    private function populateVariables($fileContent): ?string
    {
        // Set WP path
        if (!$wpPath = $this->getWpPath()) {
            return null;
        }
        $fileContent = str_replace('/path/to/wp', $wpPath, $fileContent);

        return $fileContent;
    }
}
