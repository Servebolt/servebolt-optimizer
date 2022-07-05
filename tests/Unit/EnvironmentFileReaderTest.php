<?php

namespace Unit\Traits;

use Servebolt\Optimizer\Utils\EnvFile\Reader;
use WP_UnitTestCase;
use Unit\Traits\EnvFile\EnvFileReaderTrait;

class EnvironmentFileReaderTest extends WP_UnitTestCase
{
    use EnvFileReaderTrait;

    public function setUp() : void
    {
        parent::setUp();
        Reader::toggleCache(false);
    }

    public function testThatWeCanReadFileWithAutoResolution()
    {
        $reader = self::getEnvFileReader('auto', false);
        $this->assertTrue($reader->isSuccess());
        $this->assertTrue($reader->isFileType('json'));
        $this->assertEquals($reader->bolt_id, 123456);
        $this->assertEquals($reader->id, 56789);
        $this->assertEquals($reader->name, 'Testbolt-robert');
        $this->assertEquals($reader->api_key, 'my-api-key');
    }

    public function testThatWeCanReadJsonFile()
    {
        $reader = self::getEnvFileReader('json', false);
        $this->assertTrue($reader->isSuccess());
        $this->assertTrue($reader->isFileType('json'));
        $this->assertEquals($reader->bolt_id, 123456);
        $this->assertEquals($reader->id, 56789);
        $this->assertEquals($reader->name, 'Testbolt-robert');
        $this->assertEquals($reader->api_key, 'my-api-key');
    }

    public function testThatWeCanReadIniFile()
    {
        $reader = self::getEnvFileReader('ini', false);
        $this->assertTrue($reader->isSuccess());
        $this->assertTrue($reader->isFileType('ini'));
        $this->assertEquals($reader->bolt_id, 123456);
        $this->assertEquals($reader->id, 56789);
        $this->assertEquals($reader->name, 'Testbolt-robert');
        $this->assertEquals($reader->api_key, 'my-api-key');
    }
}
