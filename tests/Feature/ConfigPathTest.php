<?php

use App\Support\UserConfig;

beforeEach(function () {
    putenv('XDG_CONFIG_HOME='.sys_get_temp_dir().'/overtimely-test-'.uniqid('', true));
});

afterEach(function () {
    $file = UserConfig::path();
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
});

it('prints the path to the config file', function () {
    $this->artisan('config:path')
        ->expectsOutputToContain(UserConfig::path())
        ->doesntExpectOutputToContain('</')
        ->assertSuccessful();
});

it('says so when the file does not exist yet', function () {
    $this->artisan('config:path')
        ->expectsOutputToContain('Run app:setup to create it.')
        ->assertSuccessful();
});

it('does not nag once the file exists', function () {
    UserConfig::save(['timely' => ['account' => ['id' => 1]]]);

    $this->artisan('config:path')
        ->doesntExpectOutputToContain('Run app:setup to create it.')
        ->assertSuccessful();
});

it('does not try to open a file that is not there', function () {
    $this->artisan('config:path', ['--open' => true])
        ->expectsOutputToContain('Run app:setup to create it.')
        ->assertSuccessful();
});
