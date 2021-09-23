<?php

namespace Unit;

use Servebolt\Optimizer\CronControl\Commands\ActionSchedulerRunMultisite;
use Servebolt\Optimizer\CronControl\Scripts\ActionSchedulerMultisiteScript\ActionSchedulerMultisiteScript;
use Servebolt\Optimizer\CronControl\Scripts\WpCronMultisiteScript\WpCronMultisiteScript;
use Servebolt\Optimizer\CronControl\Commands\WpCliEventRun;
use Servebolt\Optimizer\CronControl\Commands\WpCliEventRunMultisite;
use Servebolt\Optimizer\CronControl\Commands\ActionSchedulerRun;
use Servebolt\Optimizer\CronControl\WpCronDisabler;
use Unit\Traits\EnvFile\EnvFileReaderTrait;
use ServeboltWPUnitTestCase;
use function Servebolt\Optimizer\Helpers\wpDirectFilesystem;

class CronControlTest extends ServeboltWPUnitTestCase
{
    use EnvFileReaderTrait;

    /*
    public function testThatWeCanParseCronJobComment()
    {
        $comment = WpCliEventRun::generateComment();
        $parsedComment = CronControl::parseComment($comment);
        $this->assertArrayHasKey('name', $parsedComment);
        $this->assertArrayHasKey('rev', $parsedComment);
        $this->assertEquals(WpCliEventRun::$commandName, $parsedComment['name']);
        $this->assertEquals(WpCliEventRun::$commandRevision, $parsedComment['rev']);
    }
    */

    public function testThatWeCanControlWpCron()
    {
        add_filter('sb_optimizer_wp_config_path', function() {
            return __DIR__ . '/wp-config-sample.php';
        });
        WpCronDisabler::enableWpCron();

        $this->assertTrue(WpCronDisabler::wpCronIsEnabled());
        $this->assertFalse(WpCronDisabler::wpCronIsDisabled());

        WpCronDisabler::disableWpCron();
        $this->assertFalse(WpCronDisabler::wpCronIsEnabled());
        $this->assertTrue(WpCronDisabler::wpCronIsDisabled());

        WpCronDisabler::enableWpCron();
        $this->assertTrue(WpCronDisabler::wpCronIsEnabled());
        $this->assertFalse(WpCronDisabler::wpCronIsDisabled());

        remove_all_filters('sb_optimizer_wp_config_path');
    }

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
        $this->assertTrue(WpCliEventRun::is('wp cron event run --bogus-argument --path=/kunder/serveb_123456/optimi_56789/public'));
    }

    public function testThatWeCanGenerateWpCronEventRunMultisiteCommand()
    {
        self::getEnvFileReader();
        $command = '/kunder/serveb_123456/optimi_56789/run-wp-cron.sh';
        $comment = 'Generated by Servebolt Optimizer (name=wp-cron-multisite,rev=1)';
        $interval = '*/30 * * * *';
        $this->assertEquals('wp-cron-multisite', WpCliEventRunMultisite::$commandName);
        $this->assertEquals('1', WpCliEventRunMultisite::$commandRevision);
        $this->assertEquals($command, WpCliEventRunMultisite::generateCommand());
        $this->assertEquals($comment, WpCliEventRunMultisite::generateComment());
        $this->assertEquals($interval, WpCliEventRunMultisite::getInterval());
        $this->assertTrue(WpCliEventRunMultisite::is($command));
    }

    public function testThatWeCanGenerateActionSchedulerEventRunCommand()
    {
        self::getEnvFileReader();
        $command = 'wp action-scheduler run --path=/kunder/serveb_123456/optimi_56789/public --quiet';
        $comment = 'Generated by Servebolt Optimizer (name=action-scheduler-single-site,rev=1)';
        $interval = '* * * * *';
        $this->assertEquals('action-scheduler-single-site', ActionSchedulerRun::$commandName);
        $this->assertEquals('1', ActionSchedulerRun::$commandRevision);
        $this->assertEquals($command, ActionSchedulerRun::generateCommand());
        $this->assertEquals($comment, ActionSchedulerRun::generateComment());
        $this->assertEquals($interval, ActionSchedulerRun::getInterval());
        $this->assertTrue(ActionSchedulerRun::is('wp action-scheduler run --bogus-argument --path=/kunder/serveb_123456/optimi_56789/public'));
    }

    public function testThatWeCanGenerateActionSchedulerEventRunMultisiteCommand()
    {
        self::getEnvFileReader();
        $command = '/kunder/serveb_123456/optimi_56789/run-action-scheduler.sh';
        $comment = 'Generated by Servebolt Optimizer (name=action-scheduler-multisite,rev=1)';
        $interval = '* * * * *';
        $this->assertEquals('action-scheduler-multisite', ActionSchedulerRunMultisite::$commandName);
        $this->assertEquals('1', ActionSchedulerRunMultisite::$commandRevision);
        $this->assertEquals($command, ActionSchedulerRunMultisite::generateCommand());
        $this->assertEquals($comment, ActionSchedulerRunMultisite::generateComment());
        $this->assertEquals($interval, ActionSchedulerRunMultisite::getInterval());
        $this->assertTrue(ActionSchedulerRunMultisite::is($command));
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
