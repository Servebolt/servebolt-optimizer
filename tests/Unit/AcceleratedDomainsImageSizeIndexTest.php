<?php

namespace Unit;

use Servebolt\Optimizer\AcceleratedDomains\ImageResize\ImageIndex;
use WP_UnitTestCase;

class AcceleratedDomainsImageSizeIndexTest extends WP_UnitTestCase
{
    public function testThatWeCanAddASize(): void
    {
        $i = new ImageIndex;
        $this->assertTrue($i->addSize(69, 'w'));
    }

    public function testThatWeCanGetSizes(): void
    {
        $i = new ImageIndex;
        $i->addSize(69, 'w');
        $this->assertEquals([
            [
                'value' => 69,
                'descriptor' => 'w',
            ]
        ], $i->getSizes());
    }

    public function testThatDuplicateSizesAreIgnored()
    {
        $i = new ImageIndex;
        $i->addSize(69, 'w');
        $i->addSize(69, 'w');
        $i->addSize(512, 'w');
        $this->assertCount(2, $i->getSizes());
    }


    public function testThatWeCanRemoveASize(): void
    {
        $i = new ImageIndex;
        $i->addSize(69, 'w');
        $i->addSize(512, 'w');
        $i->addSize(1024, 'h');
        $this->assertTrue($i->removeSize(69, 'w'));
        $this->assertEquals([
            [
                'value' => 512,
                'descriptor' => 'w',
            ],
            [
                'value' => 1024,
                'descriptor' => 'h',
            ]
        ], array_values($i->getSizes()));
    }
}
