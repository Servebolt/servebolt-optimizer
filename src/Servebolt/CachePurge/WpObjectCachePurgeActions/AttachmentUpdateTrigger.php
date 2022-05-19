<?php

namespace Servebolt\Optimizer\CachePurge\WpObjectCachePurgeActions;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Throwable;
use Servebolt\Optimizer\CachePurge\CachePurge;
use Servebolt\Optimizer\CachePurge\WordPressCachePurge\WordPressCachePurge;
use Servebolt\Optimizer\Traits\EventToggler;
use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\arrayGet;
use function Servebolt\Optimizer\Helpers\setCachePurgeOriginEvent;

/**
 * Class AttachmentUpdateTrigger
 * @package Servebolt\Optimizer\CachePurge\WpObjectCachePurgeActions
 */
class AttachmentUpdateTrigger
{
    use Singleton, EventToggler;

    public function deregisterEvents(): void
    {
        remove_action('attachment_updated', [$this, 'purgeCacheForAttachment'], 10, 1);
        remove_action('wp_ajax_image-editor', [$this, 'wpImageEditorCallback'], 1);
    }

    /**
     * Register action hooks.
     */
    public function registerEvents()
    {

        // Skip this feature if automatic cache purge is inactive
        if (!CachePurge::automaticCachePurgeOnAttachmentUpdateIsActive()) {
            return;
        }

        // Should skip all automatic cache purge on content update?
        if (apply_filters('sb_optimizer_disable_automatic_purge_on_attachment_update', false)) {
            return;
        }

        // Purge post when term is edited (Work in progress)
        if (apply_filters('sb_optimizer_automatic_purge_on_attachment_metadata_generation', true)) {
            add_action('attachment_updated', [$this, 'purgeCacheForAttachment'], 10, 1);
            add_action('wp_ajax_image-editor', [$this, 'wpImageEditorCallback'], 1);
        }
    }

    /**
     * Purge cache whenever there is an AJAX-request from the image editor.
     */
    public function wpImageEditorCallback(): void
    {
        if ($this->shouldActOnThisAjaxAction() && $attachmentId = $_POST['postid']) {
            $this->purgeCacheForAttachment($attachmentId);
        }
    }

    /**
     * Check if we should act based on the "do"-parameter from the image edito.
     *
     * @return bool
     */
    private function shouldActOnThisAjaxAction(): bool
    {
        return in_array(arrayGet('do', $_POST, []), ['restore', 'save', 'scale']);
    }

    /**
     * Purge cache for attachment on metadata generation.
     *
     * @param int|mixed $attachmentId The ID of the attachment that should be purged cache for.
     */
    public function purgeCacheForAttachment($attachmentId): void
    {
        try {
            setCachePurgeOriginEvent('attachment_updated');
            WordPressCachePurge::purgeByPostId((int) $attachmentId);
        } catch (Throwable $e) {}
    }
}
