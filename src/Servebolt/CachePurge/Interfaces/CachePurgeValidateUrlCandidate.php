<?php

namespace Servebolt\Optimizer\CachePurge\Interfaces;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Interface CachePurgeValidateUrlCandidate
 * 
 * Implement this interface to validate URL candidates before trying to purge them.
 * @package Servebolt\Optimizer\CachePurge\Interfaces
 */
interface CachePurgeValidateUrlCandidate {
    public function validateUrl(string $url): bool;
}