<?php

namespace Unit\Prefetch;

use Servebolt\Optimizer\AcceleratedDomains\Prefetching\ManifestDataModel;
use Servebolt\Optimizer\AcceleratedDomains\Prefetching\ManifestFilesModel;
use Servebolt\Optimizer\AcceleratedDomains\Prefetching\ManifestFileWriter;
use Servebolt\Optimizer\AcceleratedDomains\Prefetching\ManifestHeaders;
use ServeboltWPUnitTestCase;
use Unit\Traits\MultisiteTrait;
use function Servebolt\Optimizer\Helpers\deleteOption;
use function Servebolt\Optimizer\Helpers\updateOption;

// Skip this wile we're waiting for the prefetch-feature to be completed
//class ManifestFileWriterTest extends ServeboltWPUnitTestCase
class ManifestFileWriterTest
{
    use MultisiteTrait;

    /**
     * Whether to write from the serialized data file to the JSON file.
     *
     * @var bool
     */
    private $writeFromSerializedDataToJson = true;

    /**
     * Whether to cleanup manifest files after test.
     *
     * @var bool
     */
    private $cleanupAfterTest = true;

    public function setUp() : void
    {
        //parent::setUp();
        ManifestFileWriter::shouldLimitHostname(false);
        ManifestFileWriter::shouldOrderAlphabetically(true);
        $this->setUpManifestDummyData();
    }

    public function tearDown() : void
    {
        if ($this->cleanupAfterTest) {
            ManifestFileWriter::clear(null, true);
        }
    }

    private function setUpManifestDummyData(): void
    {
        /*
        $var = json_encode(unserialize(file_get_contents(__DIR__ . '/dummy-data-serialized.txt')), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
        $var = str_replace('https://example.org', 'http://example.org', $var);
        $var = str_replace('https://acdtest.local', 'http://example.org', $var);
        file_put_contents(__DIR__ . '/dummy-data-serialized.txt', serialize(json_decode($var)));
        die;
        */

        if ($this->writeFromSerializedDataToJson) {
            file_put_contents(__DIR__ . '/dummy-data.json', json_encode(unserialize(file_get_contents(__DIR__ . '/dummy-data-serialized.txt')), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        }
        ManifestDataModel::store(json_decode(file_get_contents(__DIR__ . '/dummy-data.json'), true));
    }

    public function testThatManifestDataCanBeCleared(): void
    {
        $this->assertNotCount(0, ManifestDataModel::get());
        ManifestDataModel::clear();
        $this->assertCount(0, ManifestDataModel::get());
        $this->setUpManifestDummyData();
        $this->assertNotCount(0, ManifestDataModel::get());
    }

    public function testThatManifestDataIsPresent(): void
    {
        $data = ManifestDataModel::get();
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

        /*
        $this->assertFileExists(ManifestFileWriter::getFilePath('menu'));
        $this->assertContains('/sample-page/', file_get_contents(ManifestFileWriter::getFilePath('menu')));
        $this->assertContains('/hello-world/', file_get_contents(ManifestFileWriter::getFilePath('menu')));
        */
    }

    public function testThatWeCanUseFullUrlsInManifestFiles(): void
    {
        add_filter('sb_optimizer_prefetch_include_domain', '__return_true');
        add_filter('sb_optimizer_prefetch_item_scheme', function() {
            return 'http';
        });
        ManifestFileWriter::write();
        remove_filter('sb_optimizer_prefetch_include_domain', '__return_true');
        $this->assertFileExists(ManifestFileWriter::getFilePath('style'));
        $lines = explode(PHP_EOL, file_get_contents(ManifestFileWriter::getFilePath('style')));
        $this->assertContains(get_site_url() . '/wp-includes/css/admin-bar.min.css', $lines);
        $this->assertContains(get_site_url() . '/wp-content/plugins/servebolt-optimizer/assets/dist/css/public-style.css?ver=3.1', $lines);
    }

    public function testThatPrioritizationWorks()
    {
        ManifestFileWriter::write();
        $styleLines = explode(PHP_EOL, file_get_contents(ManifestFileWriter::getFilePath('style')));
        $this->assertContains('/wp-includes/css/dashicons.min.css', $styleLines[0]);
    }

    public function testThatWeFlaggedFilesAsWrittenToDisk(): void
    {
        ManifestFileWriter::write();
        $data = ManifestFilesModel::get();
        $this->assertIsArray($data);
        $expectedData = [
            get_site_url() . '/wp-content/uploads/acd/prefetch/manifest-style.txt',
            get_site_url() . '/wp-content/uploads/acd/prefetch/manifest-script.txt',
            //get_site_url() . '/wp-content/uploads/acd/prefetch/manifest-menu.txt',
        ];
        foreach($expectedData as $file) {
            $this->assertContains($file, $data);
        }
    }

    public function testThatManifestFilesGetsRemovedWhenDisabled(): void
    {
        ManifestFileWriter::write();
        $this->assertFileExists(ManifestFileWriter::getFilePath('script'));
        $this->assertFileExists(ManifestFileWriter::getFilePath('style'));
        //$this->assertFileExists(ManifestFileWriter::getFilePath('menu'));

        $data = ManifestFilesModel::get();
        $expectedData = [
            get_site_url() . '/wp-content/uploads/acd/prefetch/manifest-style.txt',
            get_site_url() . '/wp-content/uploads/acd/prefetch/manifest-script.txt',
            get_site_url() . '/wp-content/uploads/acd/prefetch/manifest-menu.txt',
        ];
        $this->assertEquals($expectedData, $data);

        /*
        updateOption('prefetch_file_menu_switch', 0);

        ManifestFileWriter::write();
        $this->assertFileExists(ManifestFileWriter::getFilePath('script'));
        $this->assertFileExists(ManifestFileWriter::getFilePath('style'));
        $this->assertFileNotExists(ManifestFileWriter::getFilePath('menu'));

        $data = ManifestFilesModel::get();
        $expectedData = [
            get_site_url() . '/wp-content/uploads/acd/prefetch/manifest-style.txt',
            get_site_url() . '/wp-content/uploads/acd/prefetch/manifest-script.txt',
        ];
        $this->assertEquals($expectedData, $data);
        */

        updateOption('prefetch_file_script_switch', 0);

        ManifestFileWriter::write();
        $this->assertFileNotExists(ManifestFileWriter::getFilePath('script'));
        $this->assertFileExists(ManifestFileWriter::getFilePath('style'));
        //$this->assertFileNotExists(ManifestFileWriter::getFilePath('menu'));

        $data = ManifestFilesModel::get();
        $this->assertEquals([
            get_site_url() . '/wp-content/uploads/acd/prefetch/manifest-style.txt',
        ], $data);

        //deleteOption('prefetch_file_menu_switch', 0);
        deleteOption('prefetch_file_script_switch', 0);

    }

    public function testThatWeCanLimitNumberOfLinesInFileUsingFilters()
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

    public function testThatHeadersAreSet()
    {
        ManifestFileWriter::write();
        $expected = [
            'Link: <http://example.org/wp-content/uploads/acd/prefetch/manifest-style.txt>; rel="prefetch"',
            'Link: <http://example.org/wp-content/uploads/acd/prefetch/manifest-script.txt>; rel="prefetch"',
            //'Link: <http://example.org/wp-content/uploads/acd/prefetch/manifest-menu.txt>; rel="prefetch"',
        ];
        $this->assertEquals($expected, ManifestHeaders::getHeaderItems());
    }
}
