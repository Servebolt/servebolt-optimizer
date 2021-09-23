<?php

namespace Unit;

use Servebolt\Optimizer\CronControl\Scripts\ActionSchedulerMultisiteScript\ActionSchedulerMultisiteScript;
use Servebolt\Optimizer\CronControl\Scripts\WpCronMultisiteScript\WpCronMultisiteScript;
use ServeboltWPUnitTestCase;
use Unit\Traits\EnvFile\EnvFileReaderTrait;
use function Servebolt\Optimizer\Helpers\wpDirectFilesystem;

class ScriptInstallerTest extends ServeboltWPUnitTestCase
{
    use EnvFileReaderTrait;

    public function testThatWeCanInstallMultisiteCronScript()
    {
        $i = new WpCronMultisiteScript(__DIR__);
        $this->validateScriptInstall($i);
    }

    public function testThatWeCanInstallMultisiteActionSchedulerScript()
    {
        $i = new ActionSchedulerMultisiteScript(__DIR__);
        $this->validateScriptInstall($i);
    }

    private function validateScriptInstall($i)
    {
        $customWpPath = '/custom/path/to/wp/install/folder';
        self::getEnvFileReader();
        add_filter('sb_optimizer_env_file_reader_get_public_dir', function() use ($customWpPath) { return $customWpPath; });
        $fs = wpDirectFilesystem();
        $preFilePath = $i->getFilePath();

        // Pre-cleanup
        $fs->delete($preFilePath);

        $this->assertFalse($i->isInstalled());
        $filePath = $i->install();
        $this->assertTrue($i->isInstalled());
        $this->assertEquals($preFilePath, $filePath);
        $this->assertFileExists($filePath);
        $this->assertTrue(is_executable($filePath));
        $this->assertEquals($i->getFileContent(), file_get_contents($filePath));
        $this->assertStringContainsString($customWpPath, file_get_contents($filePath));
        $this->assertTrue($i->uninstall());
        $this->assertFalse($i->isInstalled());

        // Cleanup
        remove_all_filters('sb_optimizer_env_file_reader_get_public_dir');
        $fs->delete($filePath);
    }
}
