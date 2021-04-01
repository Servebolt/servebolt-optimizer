<?php

namespace Unit\EnvFile;

use WP_UnitTestCase;
use Servebolt\Optimizer\Utils\EnvFile\Reader;

class EnvironmentFileReaderTest extends WP_UnitTestCase
{
    public function testThatWeCanReadFileWithAutoResolution()
    {
        $reader = new Reader(__DIR__ . '/');
        $this->assertTrue($reader->isSuccess());
        $this->assertTrue($reader->isFileType('json'));
        $this->assertEquals($reader->bolt_id, 123456);
        $this->assertEquals($reader->id, 56789);
        $this->assertEquals($reader->name, 'Testbolt-robert');
        $this->assertEquals($reader->api_key, 'my-api-key');
    }

    public function testThatWeCanReadJsonFile()
    {
        $reader = new Reader(__DIR__ . '/', 'json');
        $this->assertTrue($reader->isSuccess());
        $this->assertTrue($reader->isFileType('json'));
        $this->assertEquals($reader->bolt_id, 123456);
        $this->assertEquals($reader->id, 56789);
        $this->assertEquals($reader->name, 'Testbolt-robert');
        $this->assertEquals($reader->api_key, 'my-api-key');
    }

    public function testThatWeCanReadIniFile()
    {
        $reader = new Reader(__DIR__ . '/', 'ini');
        $this->assertTrue($reader->isSuccess());
        $this->assertTrue($reader->isFileType('ini'));
        $this->assertEquals($reader->bolt_id, 123456);
        $this->assertEquals($reader->id, 56789);
        $this->assertEquals($reader->name, 'Testbolt-robert');
        $this->assertEquals($reader->api_key, 'my-api-key');
    }
}
