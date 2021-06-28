<?php

namespace Unit\Prefetch;

use Unit\Traits\MultisiteTrait;
use ServeboltWPUnitTestCase;
use Servebolt\Optimizer\Prefetching\ManifestFileWriter;
use Servebolt\Optimizer\Prefetching\ManifestModel;

class ManifestFileWriterTest extends ServeboltWPUnitTestCase
{
    use MultisiteTrait;

    public function setUp()
    {
        parent::setUp();
        ManifestFileWriter::shouldLimitHostname(false);
        $this->setUpManifestData();
    }

    private function setUpManifestData(): void
    {
        ManifestModel::store(unserialize(file_get_contents(__DIR__ . '/dummy-data.txt')));
    }

    public function testThatManifestDataCanBeCleared(): void
    {
        $this->assertNotCount(0, ManifestModel::get());
        ManifestModel::clear();
        $this->assertCount(0, ManifestModel::get());
        $this->setUpManifestData();
        $this->assertNotCount(0, ManifestModel::get());
    }

    public function testThatManifestDataIsPresent(): void
    {
        $data = ManifestModel::get();
        $testKeys = [
            'servebolt-optimizer-cache-purge-trigger-scripts',
        ];
        $this->assertArrayHasKey('script', $data);
        foreach($testKeys as $testKey) {
            $this->assertArrayHasKey($testKey, $data['script']);
            $this->assertContains('wp-content/plugins/servebolt-optimizer/assets/dist/js/cache-purge-trigger.js', $data['script'][$testKey]['src']);
        }
    }

    public function testThatManifestFileGetsWrittenToDisk(): void
    {
        ManifestFileWriter::write();
        $this->assertFileExists(ManifestFileWriter::getFilePath());
        $this->assertContains('cache-purge-trigger.js', file_get_contents(ManifestFileWriter::getFilePath()));
        $this->assertContains('admin-bar.min.js', file_get_contents(ManifestFileWriter::getFilePath()));
    }
}
