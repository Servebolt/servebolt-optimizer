<?php

namespace Unit;

use Servebolt\Optimizer\CronControl\CronControl;
use Servebolt\Optimizer\CronControl\MultisiteCronInstaller\MultisiteCronInstaller;
use Servebolt\Optimizer\CronControl\Cronjobs\WpCliEventRun;
use Unit\Traits\EnvFile\EnvFileReaderTrait;
use ServeboltWPUnitTestCase;
use function Servebolt\Optimizer\Helpers\wpDirectFilesystem;

class CronControlTest extends ServeboltWPUnitTestCase
{
    use EnvFileReaderTrait;

    public function testThatWeCanControlWpCron()
    {
        add_filter('sb_optimizer_wp_config_path', function() {
            return __DIR__ . '/wp-config-sample.php';
        });
        CronControl::enableWpCron();

        $this->assertTrue(CronControl::wpCronIsEnabled());
        $this->assertFalse(CronControl::wpCronIsDisabled());

        CronControl::disableWpCron();
        $this->assertFalse(CronControl::wpCronIsEnabled());
        $this->assertTrue(CronControl::wpCronIsDisabled());

        CronControl::enableWpCron();
        $this->assertTrue(CronControl::wpCronIsEnabled());
        $this->assertFalse(CronControl::wpCronIsDisabled());

        remove_all_filters('sb_optimizer_wp_config_path');
    }

    public function testThatWeCanInstallMultisiteCronScript()
    {
        $installPath = __DIR__;
        $customWpPath = '/custom/path/to/wp/install/folder';
        $fs = wpDirectFilesystem();
        self::getEnvFileReader();
        add_filter('sb_optimizer_env_file_reader_get_public_dir', function() use ($customWpPath) { return $customWpPath; });

        // Pre-cleanup
        $preFilePath = MultisiteCronInstaller::getFilePath($installPath);
        $fs->delete($preFilePath);

        $this->assertFalse(MultisiteCronInstaller::isInstalled($installPath));
        $filePath = MultisiteCronInstaller::install($installPath);
        $this->assertTrue(MultisiteCronInstaller::isInstalled($installPath));
        $this->assertEquals($preFilePath, $filePath);
        $this->assertFileExists($filePath);
        $this->assertTrue(is_executable($filePath));
        $this->assertEquals(MultisiteCronInstaller::getFileContent(), file_get_contents($filePath));
        $this->assertStringContainsString($customWpPath, file_get_contents($filePath));

        // Cleanup
        remove_all_filters('sb_optimizer_env_file_reader_get_public_dir');
        $fs->delete($filePath);
    }

    public function testThatWeCanGenerateWpCronEventRunCommand()
    {
        self::getEnvFileReader();
        $command = 'wp cron event run --due-now --path=/kunder/serveb_123456/optimi_56789/public --quiet';
        $comment = 'Generated by Servebolt Optimizer (name=wp-cron-single-site,rev=1)';
        $interval = '*/10 * * * *';
        $this->assertEquals('wp-cron-single-site', WpCliEventRun::$commandName);
        $this->assertEquals('1', WpCliEventRun::$commandRevision);
        $this->assertEquals($command, WpCliEventRun::generateCommand());
        $this->assertEquals($comment, WpCliEventRun::generateComment());
        $this->assertEquals($interval, WpCliEventRun::getInterval());
        $this->assertEquals($interval . ' ' . $command, WpCliEventRun::generateCronjob(false));
        $this->assertEquals($interval . ' ' . $command . ' ' . $comment, WpCliEventRun::generateCronjob());
    }
}