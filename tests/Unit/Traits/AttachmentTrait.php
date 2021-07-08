<?php

namespace Unit\Traits;

use DOMDocument;

/**
 * Trait AttachmentTrait
 * @package Unit
 */
trait AttachmentTrait
{
    /**
     * @param int $attachmentId
     * @return \DOMNode|null
     */
    private function getImageMarkupDom(int $attachmentId)
    {
        $imageMarkup = wp_get_attachment_image($attachmentId, 'original');
        $dom = new DOMDocument;
        @$dom->loadHTML($imageMarkup);
        $items = $dom->getElementsByTagName('img');
        return $items->item(0);
    }

    /**
     * Delete attachment.
     *
     * @param int $attachmentId
     */
    private function deleteAttachment(int $attachmentId): void
    {
        wp_delete_attachment($attachmentId, true);
    }

    /**
     * Create attachment from given test file.
     *
     * @param string $filename The name of the file to be uploaded.
     * @return false|int|\WP_Error
     */
    private function createAttachment(string $filename)
    {
        $filePath = trailingslashit(WP_TESTS_DIR) . 'TestFiles/' . $filename;
        if (!file_exists($filePath)) {
            return false; // Test file does not exists
        }

        // Create temporary file, fill with content, and get path
        $tempFile = tmpfile();
        fwrite($tempFile, file_get_contents($filePath));
        $tempFileMetaData = stream_get_meta_data($tempFile);
        $tempFilePath = $tempFileMetaData['uri'];

        $attachmentId = media_handle_sideload([
            'name' => $filename,
            'tmp_name' => $tempFilePath,
        ]);

        if (is_wp_error($attachmentId)) {
            $this->fail('Could not upload test-attachment.');
            wp_delete_attachment($attachmentId);
            return false;
        }

        return $attachmentId;
    }
}
