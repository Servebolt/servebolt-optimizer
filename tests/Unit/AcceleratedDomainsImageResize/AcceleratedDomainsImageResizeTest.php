<?php

namespace Unit;

use WP_UnitTestCase;
use Servebolt\Optimizer\AcceleratedDomains\ImageResize\FeatureAccess;
use Servebolt\Optimizer\Utils\EnvironmentConfig;
use Unit\Traits\AttachmentTrait;
use Servebolt\Optimizer\AcceleratedDomains\ImageResize\AcceleratedDomainsImageResize;
use Servebolt\Optimizer\AcceleratedDomains\ImageResize\ImageResize;
use function Servebolt\Optimizer\Helpers\setOptionOverride;

class AcceleratedDomainsImageResizeTest extends WP_UnitTestCase
{
    use AttachmentTrait;

    public function testThatWeCanCheckFeatureAccess()
    {
        $config = EnvironmentConfig::getInstance();
        $this->assertFalse(FeatureAccess::hasAccess());
        $config->setConfigObject((object) ['sb_acd_image_resize' => true]);
        $this->assertTrue(FeatureAccess::hasAccess());
        $config->reset();
        $this->assertFalse(FeatureAccess::hasAccess());
    }

    public function setUp()
    {
        parent::setUp();
        $this->ir = new ImageResize;
        setOptionOverride('acd_image_resize_switch', '__return_true');
        setOptionOverride('acd_image_resize_src_tag_switch', '__return_true');
        new AcceleratedDomainsImageResize;
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
        $expectedUrl = 'https://some-domain.com/acd-cgi/img/v1/wp-content/uploads/woocommerce-placeholder.png?width=800';
        $attachmentData = ['https://some-domain.com/wp-content/uploads/woocommerce-placeholder.png', 800, 800, false];
        $modifiedAttachmentData = $this->ir->alterSingleImageUrl($attachmentData);
        $this->assertEquals($expectedUrl, $modifiedAttachmentData[0]);
    }

    public function testThatDefaultImageQualityIsSet()
    {
        $attachmentData = ['https://some-domain.com/wp-content/uploads/woocommerce-placeholder.png', 800, 800, false];
        $modifiedAttachmentData = $this->ir->alterSingleImageUrl($attachmentData);
        $this->assertNotContains('quality=85', $modifiedAttachmentData[0]);

        $this->ir->setImageQuality(69);
        $modifiedAttachmentData = $this->ir->alterSingleImageUrl($attachmentData);
        $this->assertContains('quality=69', $modifiedAttachmentData[0]);

        $this->ir->setImageQuality(null);
        $modifiedAttachmentData = $this->ir->alterSingleImageUrl($attachmentData);
        $this->assertNotContains('quality=69', $modifiedAttachmentData[0]);
    }

    public function testThatWeCanSetMetadataOptimizationLevels()
    {
        $attachmentData = ['https://some-domain.com/wp-content/uploads/woocommerce-placeholder.png', 800, 800, false];
        $modifiedAttachmentData = $this->ir->alterSingleImageUrl($attachmentData);
        $this->assertEquals('https://some-domain.com/acd-cgi/img/v1/wp-content/uploads/woocommerce-placeholder.png?width=800', $modifiedAttachmentData[0]);

        $this->ir->setMetadataOptimizationLevel('none');
        $modifiedAttachmentData = $this->ir->alterSingleImageUrl($attachmentData);
        $this->assertEquals('https://some-domain.com/acd-cgi/img/v1/wp-content/uploads/woocommerce-placeholder.png?metadata=none&width=800', $modifiedAttachmentData[0]);

        $this->ir->setMetadataOptimizationLevel(null);
        $modifiedAttachmentData = $this->ir->alterSingleImageUrl($attachmentData);
        $this->assertEquals('https://some-domain.com/acd-cgi/img/v1/wp-content/uploads/woocommerce-placeholder.png?width=800', $modifiedAttachmentData[0]);

        $this->ir->setMetadataOptimizationLevel('copyright');
        $modifiedAttachmentData = $this->ir->alterSingleImageUrl($attachmentData);
        $this->assertEquals('https://some-domain.com/acd-cgi/img/v1/wp-content/uploads/woocommerce-placeholder.png?width=800', $modifiedAttachmentData[0]);

        $this->ir->setMetadataOptimizationLevel('keep');
        $modifiedAttachmentData = $this->ir->alterSingleImageUrl($attachmentData);
        $this->assertEquals('https://some-domain.com/acd-cgi/img/v1/wp-content/uploads/woocommerce-placeholder.png?metadata=keep&width=800', $modifiedAttachmentData[0]);
    }

    /*
    public function testThatDefaultImageFormatIsSet()
    {
        $attachmentData = ['https://some-domain.com/wp-content/uploads/woocommerce-placeholder.png', 800, 800, false];
        $modifiedAttachmentData = $this->ir->alterSingleImageUrl($attachmentData);
        $this->assertContains('format=auto', $modifiedAttachmentData[0]);
    }
    */

    public function testMaxImageDimensionFilters(): void
    {
        add_filter('sb_optimizer_acd_image_resize_max_width', function () {
            return 2000;
        });
        add_filter('sb_optimizer_acd_image_resize_max_height', function () {
            return 2000;
        });

        $expectedUrl = 'https://some-domain.com/acd-cgi/img/v1/wp-content/uploads/woocommerce-placeholder.png?width=2000&height=2000';
        $attachmentData = ['https://some-domain.com/wp-content/uploads/woocommerce-placeholder.png', 2500, 2500, false];
        $modifiedAttachmentData = $this->ir->alterSingleImageUrl($attachmentData);
        $this->assertEquals($expectedUrl, $modifiedAttachmentData[0]);

        remove_all_filters('sb_optimizer_acd_image_resize_max_width');
        remove_all_filters('sb_optimizer_acd_image_resize_max_height');
    }

    public function testThatMaxHeightAndWidthDimensionsGetsApplied()
    {
        // Width and height exceeds the max dimension
        $expectedUrl = 'https://some-domain.com/acd-cgi/img/v1/wp-content/uploads/woocommerce-placeholder.png?width=1920&height=1080';
        $attachmentData = ['https://some-domain.com/wp-content/uploads/woocommerce-placeholder.png', '2000', '2000', false];
        $modifiedAttachmentData = $this->ir->alterSingleImageUrl($attachmentData);
        $this->assertEquals($expectedUrl, $modifiedAttachmentData[0]);

        // Only width exceeds the max dimension
        $expectedUrl = 'https://some-domain.com/acd-cgi/img/v1/wp-content/uploads/woocommerce-placeholder.png?width=1920';
        $attachmentData = ['https://some-domain.com/wp-content/uploads/woocommerce-placeholder.png', 2000, 1000, false];
        $modifiedAttachmentData = $this->ir->alterSingleImageUrl($attachmentData);
        $this->assertEquals($expectedUrl, $modifiedAttachmentData[0]);

        // Only height exceeds the max dimension
        $expectedUrl = 'https://some-domain.com/acd-cgi/img/v1/wp-content/uploads/woocommerce-placeholder.png?width=1000&height=1080';
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
        $expectedUrl = 'https://some-domain.com/acd-cgi/img/v1/wp-content/uploads/woocommerce-placeholder.png?width=1000&height=1000';
        $attachmentData = ['https://some-domain.com/wp-content/uploads/woocommerce-placeholder.png', '1000', '1000', false];
        $modifiedAttachmentData = $this->ir->alterSingleImageUrl($attachmentData);
        $this->assertEquals($expectedUrl, $modifiedAttachmentData[0]);
        remove_all_filters('sb_optimizer_acd_image_resize_max_height');
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
            $this->assertContains('/acd-cgi/img/v1/', $image->getAttribute('srcset')); // Cannot get srcset-for some reason
            $this->deleteAttachment($attachmentId);
        }
    }

    public function testThatSvgFilesAreIgnored(): void
    {
        if ($attachmentId = $this->createAttachment('apache.svg')) {
            $image = $this->getImageMarkupDom($attachmentId);
            if (!$image) {
                $this->fail('Could not select image in markup string.');
                return;
            }
            $this->assertNotContains('/acd-cgi/img/v1/', $image->getAttribute('src'));
            $this->assertNotContains('/acd-cgi/img/v1', $image->getAttribute('srcset')); // Cannot get srcset-for some reason
            $this->deleteAttachment($attachmentId);
        }
    }

    public function testThatSvgFilesWithWrongMimeTypeAreIgnored(): void
    {
        if ($attachmentId = $this->createAttachment('apache.svg')) {
            // Simulate a SVG-file with wrong MIME-type
            wp_update_post([
                'ID' => $attachmentId,
                'post_mime_type' => 'image/jpeg'
            ]);
            $image = $this->getImageMarkupDom($attachmentId);
            if (!$image) {
                $this->fail('Could not select image in markup string.');
                return;
            }
            $this->assertNotContains('/acd-cgi/img/v1/', $image->getAttribute('src'));
            $this->assertNotContains('/acd-cgi/img/v1', $image->getAttribute('srcset')); // Cannot get srcset-for some reason
            $this->deleteAttachment($attachmentId);
        }
    }
}
