<?php

namespace Unit;

use Servebolt\Optimizer\AcceleratedDomains\ImageResize\ImageSizeIndexModel;
use WP_UnitTestCase;

class AcceleratedDomainsImageSizeIndexTest extends WP_UnitTestCase
{
    public function testThatWeCanAddASize(): void
    {
        $this->assertTrue(ImageSizeIndexModel::addSize(69, 'w'));
    }

    public function testThatWeCanGetSizes(): void
    {
        ImageSizeIndexModel::addSize(69, 'w');
        $this->assertEquals([
            [
                'value' => 69,
                'descriptor' => 'w',
            ]
        ], ImageSizeIndexModel::getSizes());
    }

    public function testThatDuplicateSizesAreIgnored()
    {
        ImageSizeIndexModel::addSize(69, 'w');
        ImageSizeIndexModel::addSize(69, 'w');
        ImageSizeIndexModel::addSize(512, 'w');
        $this->assertCount(2, ImageSizeIndexModel::getSizes());
    }

    public function testThatWeCanCheckIfASizeExists()
    {
        ImageSizeIndexModel::addSize(69, 'w');
        ImageSizeIndexModel::addSize(512, 'w');
        ImageSizeIndexModel::addSize(1024, 'h');
        $this->assertTrue(ImageSizeIndexModel::sizeExists(69, 'w'));
        $this->assertFalse(ImageSizeIndexModel::sizeExists(67, 'w'));
        $this->assertTrue(ImageSizeIndexModel::sizeExists(1024, 'h'));
    }

    public function testThatWeCanRemoveASize(): void
    {
        ImageSizeIndexModel::addSize(69, 'w');
        ImageSizeIndexModel::addSize(512, 'w');
        ImageSizeIndexModel::addSize(1024, 'h');
        $this->assertTrue(ImageSizeIndexModel::removeSize(69, 'w'));
        $this->assertEquals([
            [
                'value' => 512,
                'descriptor' => 'w',
            ],
            [
                'value' => 1024,
                'descriptor' => 'h',
            ]
        ], array_values(ImageSizeIndexModel::getSizes()));
    }
}
