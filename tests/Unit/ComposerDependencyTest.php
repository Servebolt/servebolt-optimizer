<?php

namespace Unit;

use WP_UnitTestCase;

class ComposerDependencyTest extends WP_UnitTestCase
{
    public function testThatGuzzlePackageIsLoadedNamespacePrefixed()
    {
        $this->assertTrue(class_exists('\\Servebolt\\Optimizer\\Dependencies\\GuzzleHttp\\Client'));
        $this->assertTrue(class_exists('\\Servebolt\\Optimizer\\Dependencies\\GuzzleHttp\\Psr7\\Response'));
        $this->assertTrue(class_exists('\\Servebolt\\Optimizer\\Dependencies\\GuzzleHttp\\Promise\\Promise'));

        $this->assertFalse(class_exists('\\GuzzleHttp\\Client'));
        $this->assertFalse(class_exists('\\GuzzleHttp\\Psr7\\Response'));
        $this->assertFalse(class_exists('\\GuzzleHttp\\Promise\\Promise'));
    }

    public function testThatPsrPackageIsLoadedNamespacePrefixed()
    {
        $this->assertTrue(interface_exists('\\Servebolt\\Optimizer\\Dependencies\\Psr\\Http\\Client\\ClientInterface'));
        $this->assertTrue(interface_exists('\\Servebolt\\Optimizer\\Dependencies\\Psr\\Http\\Message\\MessageInterface'));

        $this->assertFalse(interface_exists('\\Psr\\Http\\Client\\ClientInterface'));
        $this->assertFalse(interface_exists('\\Psr\\Http\\Message\\MessageInterface'));
    }
}
