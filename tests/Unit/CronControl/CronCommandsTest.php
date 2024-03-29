<?php

namespace Unit\CronControl;

use Servebolt\Optimizer\CronControl\Commands\ActionSchedulerRun;
use Servebolt\Optimizer\CronControl\Commands\ActionSchedulerRunMultisite;
use Servebolt\Optimizer\CronControl\Commands\WpCliEventRun;
use Servebolt\Optimizer\CronControl\Commands\WpCliEventRunMultisite;
use ServeboltWPUnitTestCase;
use Unit\Traits\EnvFile\EnvFileReaderTrait;

class CronCommandsTest extends ServeboltWPUnitTestCase
{
    use EnvFileReaderTrait;

    private $intervalRegexPattern = '/^(([0-5]?[0-9]),){11}([0-5]?[0-9]) \* \* \* \*$/';

    public function testThatWeCanGenerateWpCronEventRunCommand()
    {
        self::getEnvFileReader();
        $preCommand = 'flock -n ~/.wp_cron.lock';
        $command = 'wp cron event run --due-now --path=/kunder/serveb_123456/optimi_56789/public --quiet';
        $comment = 'Generated by Servebolt Optimizer (name=wp-cron-single-site,rev=1)';
        $this->assertEquals('wp-cron-single-site', WpCliEventRun::$commandName);
        $this->assertEquals('1', WpCliEventRun::$commandRevision);
        $this->assertEquals($preCommand . ' ' . $command, WpCliEventRun::generateCommand());
        $this->assertEquals($comment, WpCliEventRun::generateComment());
        $this->assertRegExp($this->intervalRegexPattern, WpCliEventRun::getInterval());
        $this->assertTrue(WpCliEventRun::is('wp cron event run --bogus-argument --path=/kunder/serveb_123456/optimi_56789/public'));
    }

    public function testThatWeCanGenerateWpCronEventRunMultisiteCommand()
    {
        self::getEnvFileReader();
        $command = '/kunder/serveb_123456/optimi_56789/run-wp-cron.sh';
        $comment = 'Generated by Servebolt Optimizer (name=wp-cron-multisite,rev=1)';
        $this->assertEquals('wp-cron-multisite', WpCliEventRunMultisite::$commandName);
        $this->assertEquals('1', WpCliEventRunMultisite::$commandRevision);
        $this->assertEquals($command, WpCliEventRunMultisite::generateCommand());
        $this->assertEquals($comment, WpCliEventRunMultisite::generateComment());
        $this->assertRegExp($this->intervalRegexPattern, WpCliEventRun::getInterval());
        $this->assertTrue(WpCliEventRunMultisite::is($command));
    }

    public function testThatWeCanGenerateActionSchedulerEventRunCommand()
    {
        self::getEnvFileReader();
        $preCommand = 'flock -n ~/.wp_cron_as.lock';
        $command = 'wp action-scheduler run --path=/kunder/serveb_123456/optimi_56789/public --quiet';
        $comment = 'Generated by Servebolt Optimizer (name=action-scheduler-single-site,rev=1)';
        $this->assertEquals('action-scheduler-single-site', ActionSchedulerRun::$commandName);
        $this->assertEquals('1', ActionSchedulerRun::$commandRevision);
        $this->assertEquals($preCommand . ' ' . $command, ActionSchedulerRun::generateCommand());
        $this->assertEquals($comment, ActionSchedulerRun::generateComment());
        $this->assertRegExp($this->intervalRegexPattern, WpCliEventRun::getInterval());
        $this->assertTrue(ActionSchedulerRun::is('wp action-scheduler run --bogus-argument --path=/kunder/serveb_123456/optimi_56789/public'));
    }

    public function testThatWeCanGenerateActionSchedulerEventRunMultisiteCommand()
    {
        self::getEnvFileReader();
        $command = '/kunder/serveb_123456/optimi_56789/run-action-scheduler.sh';
        $comment = 'Generated by Servebolt Optimizer (name=action-scheduler-multisite,rev=1)';
        $this->assertEquals('action-scheduler-multisite', ActionSchedulerRunMultisite::$commandName);
        $this->assertEquals('1', ActionSchedulerRunMultisite::$commandRevision);
        $this->assertEquals($command, ActionSchedulerRunMultisite::generateCommand());
        $this->assertEquals($comment, ActionSchedulerRunMultisite::generateComment());
        $this->assertRegExp($this->intervalRegexPattern, WpCliEventRun::getInterval());
        $this->assertTrue(ActionSchedulerRunMultisite::is($command));
    }
}
