<?php

namespace Tests;

use App\Support\UserConfig;
use LaravelZero\Framework\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * The config home has to point at a throwaway directory before the application boots,
     * because booting merges the user config file into the config repository.
     */
    protected function setUp(): void
    {
        putenv('XDG_CONFIG_HOME='.sys_get_temp_dir().DIRECTORY_SEPARATOR.'overtimely-test-'.uniqid('', true));

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $file = UserConfig::path();

        parent::tearDown();

        if (is_file($file)) {
            unlink($file);
        }

        $dir = dirname($file);

        if (is_dir($dir)) {
            rmdir($dir);
        }

        $home = getenv('XDG_CONFIG_HOME');

        if (is_string($home) && is_dir($home)) {
            rmdir($home);
        }

        putenv('XDG_CONFIG_HOME');
    }
}
