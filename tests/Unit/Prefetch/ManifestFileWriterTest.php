<?php

namespace Unit\Prefetch;

use Unit\Traits\MultisiteTrait;
use ServeboltWPUnitTestCase;
use Servebolt\Optimizer\Prefetching\ManifestFileWriter;
use Servebolt\Optimizer\Prefetching\ManifestModel;

class ManifestFileWriterTest extends ServeboltWPUnitTestCase
{
    use MultisiteTrait;

    /**
     * Whether to write from the serialized data file to the JSON file.
     *
     * @var bool
     */
    private $writeFromSerializedDataToJson = true;

    public function setUp()
    {
        parent::setUp();
        ManifestFileWriter::shouldLimitHostname(false);
        $this->setUpManifestDummyData();
    }

    public function tearDown()
    {
        ManifestFileWriter::clear(null, true);
    }

    private function setUpManifestDummyData(): void
    {
        if ($this->writeFromSerializedDataToJson) {
            file_put_contents(__DIR__ . '/dummy-data.json', json_encode(unserialize(file_get_contents(__DIR__ . '/dummy-data-serialized.txt')), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        }
        ManifestModel::store(json_decode(file_get_contents(__DIR__ . '/dummy-data.json'), true));
    }

    public function testThatManifestDataCanBeCleared(): void
    {
        $this->assertNotCount(0, ManifestModel::get());
        ManifestModel::clear();
        $this->assertCount(0, ManifestModel::get());
        $this->setUpManifestDummyData();
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

    public function testThatManifestFilesGetsWrittenToDisk(): void
    {
        ManifestFileWriter::write();
        $this->assertFileExists(ManifestFileWriter::getFilePath('script'));
        $this->assertContains('cache-purge-trigger.js', file_get_contents(ManifestFileWriter::getFilePath('script')));
        $this->assertContains('admin-bar.min.js', file_get_contents(ManifestFileWriter::getFilePath('script')));

        $this->assertFileExists(ManifestFileWriter::getFilePath('style'));
        $this->assertContains('/wp-includes/css/admin-bar.min.css', file_get_contents(ManifestFileWriter::getFilePath('style')));
        $this->assertContains('/wp-content/plugins/servebolt-optimizer/assets/dist/css/public-style.css', file_get_contents(ManifestFileWriter::getFilePath('style')));

        $this->assertFileExists(ManifestFileWriter::getFilePath('menu'));
        $this->assertContains('https://acdtest.local/sample-page/', file_get_contents(ManifestFileWriter::getFilePath('menu')));
        $this->assertContains('https://acdtest.local/hello-world/', file_get_contents(ManifestFileWriter::getFilePath('menu')));
    }

    public function testThatWeCanLimitNumberOfLinesInFile()
    {
        $maxNumberOfLines = 2;
        add_filter('sb_optimizer_prefetch_max_number_of_lines', function() use ($maxNumberOfLines) {
            return $maxNumberOfLines;
        });
        ManifestFileWriter::write();
        $scriptFilePath = ManifestFileWriter::getFilePath('script');
        $this->assertFileExists($scriptFilePath);
        $this->assertCount($maxNumberOfLines, explode(PHP_EOL, file_get_contents($scriptFilePath)));

        remove_all_filters('sb_optimizer_prefetch_max_number_of_lines');
        $this->setUpManifestDummyData();
        ManifestFileWriter::write();
        $this->assertCount(9, explode(PHP_EOL, file_get_contents($scriptFilePath)));
    }
}
