<?php

/**
 * Class AttachmentTrigger
 */
class AttachmentTrigger
{
    /**
     * AttachmentTrigger constructor.
     */
    public function __construct()
    {
        add_filter('wp_generate_attachment_metadata', [$this, 'purgeCacheForAttachment'], 10, 3);
    }

    public function ape(array $metaData, int $attachmentId, string $context): array
    {
        foreach($metaData['sizes'] as $size) {
            
        }
        return $metaData;
    }
}
