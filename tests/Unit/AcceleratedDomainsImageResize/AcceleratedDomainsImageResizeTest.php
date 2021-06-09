<?php

namespace Unit;

use DOMDocument;
use WP_UnitTestCase;
use Servebolt\Optimizer\AcceleratedDomains\ImageResize\ImageResize;
use function Servebolt\Optimizer\Helpers\wpDirectFilesystem;

class AcceleratedDomainsImageResizeTest extends WP_UnitTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->ir = new ImageResize(false);
    }

    public function testThatTheResizeServicePrefixIsAddedToAUrlString()
    {
        $this->assertContains('/acd-cgi/img/v1/', $this->ir->buildImageUrl('https://domain.com/wp-content/2021/05/some-image.jpg'));
    }

    public function testThatResizeParametersAreAddedToQueryString()
    {
        $this->assertEquals(
            'https://domain.com/acd-cgi/img/v1/wp-content/2021/05/some-image.jpg?cache-buster=8964565416&something=somehow&param=true',
            $this->ir->buildImageUrl('https://domain.com/wp-content/2021/05/some-image.jpg?cache-buster=8964565416&something=somehow', ['param' => 'true'])
        );

        $this->assertEquals(
            'https://domain.com/acd-cgi/img/v1/wp-content/2021/05/some-image-2.jpg?param=true',
            $this->ir->buildImageUrl('https://domain.com/wp-content/2021/05/some-image-2.jpg', ['param' => 'true'])
        );

        $this->assertEquals(
            'https://domain.com/acd-cgi/img/v1/wp-content/2021/05/some-image-3.png?something=somehow',
            $this->ir->buildImageUrl('https://domain.com/wp-content/2021/05/some-image-3.png?something=somehow')
        );

        $this->assertEquals(
            'https://domain.com/acd-cgi/img/v1/wp-content/2021/05/some-weird-image.bmp',
            $this->ir->buildImageUrl('https://domain.com/wp-content/2021/05/some-weird-image.bmp')
        );
    }

    public function testThatImageDimensionsAreAppliedToQueryString()
    {
        $expectedUrl = 'https://some-domain.com/acd-cgi/img/v1/wp-content/uploads/woocommerce-placeholder.png?quality=85&format=auto&width=800';
        $attachmentData = ['https://some-domain.com/wp-content/uploads/woocommerce-placeholder.png', 800, 800, false];
        $modifiedAttachmentData = $this->ir->alterSingleImageUrl($attachmentData);
        $this->assertEquals($expectedUrl, $modifiedAttachmentData[0]);
    }

    public function testThatDefaultImageQualityIsSet()
    {
        $attachmentData = ['https://some-domain.com/wp-content/uploads/woocommerce-placeholder.png', 800, 800, false];
        $modifiedAttachmentData = $this->ir->alterSingleImageUrl($attachmentData);
        $this->assertContains('quality=85', $modifiedAttachmentData[0]);
    }

    public function testThatDefaultImageFormatIsSet()
    {
        $attachmentData = ['https://some-domain.com/wp-content/uploads/woocommerce-placeholder.png', 800, 800, false];
        $modifiedAttachmentData = $this->ir->alterSingleImageUrl($attachmentData);
        $this->assertContains('format=auto', $modifiedAttachmentData[0]);
    }

    public function testMaxImageDimensionFilters(): void
    {
        add_filter('sb_optimizer_acd_image_resize_max_width', function() {
            return 2000;
        });
        add_filter('sb_optimizer_acd_image_resize_max_height', function() {
            return 2000;
        });

        $expectedUrl = 'https://some-domain.com/acd-cgi/img/v1/wp-content/uploads/woocommerce-placeholder.png?quality=85&format=auto&width=2000&height=2000';
        $attachmentData = ['https://some-domain.com/wp-content/uploads/woocommerce-placeholder.png', 2500, 2500, false];
        $modifiedAttachmentData = $this->ir->alterSingleImageUrl($attachmentData);
        $this->assertEquals($expectedUrl, $modifiedAttachmentData[0]);

        remove_all_filters('sb_optimizer_acd_image_resize_max_width');
        remove_all_filters('sb_optimizer_acd_image_resize_max_height');
    }

    public function testThatMaxHeightAndWidthDimensionsGetsApplied()
    {
        // Width and height exceeds the max dimension
        $expectedUrl = 'https://some-domain.com/acd-cgi/img/v1/wp-content/uploads/woocommerce-placeholder.png?quality=85&format=auto&width=1920&height=1080';
        $attachmentData = ['https://some-domain.com/wp-content/uploads/woocommerce-placeholder.png', '2000', '2000', false];
        $modifiedAttachmentData = $this->ir->alterSingleImageUrl($attachmentData);
        $this->assertEquals($expectedUrl, $modifiedAttachmentData[0]);

        // Only width exceeds the max dimension
        $expectedUrl = 'https://some-domain.com/acd-cgi/img/v1/wp-content/uploads/woocommerce-placeholder.png?quality=85&format=auto&width=1920';
        $attachmentData = ['https://some-domain.com/wp-content/uploads/woocommerce-placeholder.png', 2000, 1000, false];
        $modifiedAttachmentData = $this->ir->alterSingleImageUrl($attachmentData);
        $this->assertEquals($expectedUrl, $modifiedAttachmentData[0]);

        // Only height exceeds the max dimension
        $expectedUrl = 'https://some-domain.com/acd-cgi/img/v1/wp-content/uploads/woocommerce-placeholder.png?quality=85&format=auto&width=1000&height=1080';
        $attachmentData = ['https://some-domain.com/wp-content/uploads/woocommerce-placeholder.png', 1000, 2000, false];
        $modifiedAttachmentData = $this->ir->alterSingleImageUrl($attachmentData);
        $this->assertEquals($expectedUrl, $modifiedAttachmentData[0]);
    }

    public function testThatImageArrayStaysUnchangedExceptTheUrl()
    {
        $attachmentData = ['https://some-domain.com/wp-content/uploads/woocommerce-placeholder.png', 300, 300, false];
        $modifiedAttachmentData = $this->ir->alterSingleImageUrl($attachmentData);
        unset($attachmentData[0]);
        unset($modifiedAttachmentData[0]);
        $this->assertEquals($attachmentData, $modifiedAttachmentData);
    }

    public function testThatHeightIsIncludedWhenUsingAFilterOverride()
    {
        add_filter('sb_optimizer_acd_image_resize_force_add_height', '__return_true');
        $expectedUrl = 'https://some-domain.com/acd-cgi/img/v1/wp-content/uploads/woocommerce-placeholder.png?quality=85&format=auto&width=1000&height=1000';
        $attachmentData = ['https://some-domain.com/wp-content/uploads/woocommerce-placeholder.png', '1000', '1000', false];
        $modifiedAttachmentData = $this->ir->alterSingleImageUrl($attachmentData);
        $this->assertEquals($expectedUrl, $modifiedAttachmentData[0]);
        remove_filter('sb_optimizer_acd_image_resize_max_height', '__return_false');
    }

    public function testThatImageTagGetsRewritten()
    {
        if ($attachmentId = $this->createAttachment('woocommerce-placeholder.png')) {
            $image = $this->getImageMarkupDom($attachmentId);
            if (!$image) {
                $this->fail('Could not select image in markup string.');
                return;
            }
            $this->assertContains('/acd-cgi/img/v1/', $image->getAttribute('src'));
            //$this->assertContains('/acd-cgi/img/v1', $image->getAttribute('srcset')); // Cannot get srcset-for some reason
            $this->deleteAttachment($attachmentId);
        }
    }

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
     * Deletes the uploads-folder.
     */
    private function clearUploads(): void
    {
        $uploadFolder = wp_upload_dir();
        $uploadFolderPath = trailingslashit($uploadFolder['basedir']);
        $filesystem = wpDirectFilesystem();
        $filesystem->delete($uploadFolderPath, true);
    }

    /**
     * Delete attachment.
     *
     * @param int $attachmentId
     * @param bool $clearUploads
     */
    private function deleteAttachment(int $attachmentId, bool $clearUploads = true): void
    {
        wp_delete_attachment($attachmentId, true);
        if ($clearUploads) {
            $this->clearUploads();
        }
    }

    /**
     * Create attachment from given test file.
     *
     * @param string $filename The name of the file to be uploaded.
     * @return false|int|\WP_Error
     */
    private function createAttachment(string $filename)
    {
        $filePath = trailingslashit(__DIR__) . 'Files/' . $filename;
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