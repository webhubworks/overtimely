<?php

use App\Support\UserConfig;

it('prints the path to the config file', function () {
    $this->artisan('config:path')
        ->expectsOutputToContain(UserConfig::path())
        ->doesntExpectOutputToContain('</')
        ->assertSuccessful();
});

it('says so when the file does not exist yet', function () {
    $this->artisan('config:path')
        ->expectsOutputToContain('Run config:setup to create it.')
        ->assertSuccessful();
});

it('does not nag once the file exists', function () {
    UserConfig::save(['timely' => ['account' => ['id' => 1]]]);

    $this->artisan('config:path')
        ->doesntExpectOutputToContain('Run config:setup to create it.')
        ->assertSuccessful();
});

it('does not try to open a file that is not there', function () {
    $this->artisan('config:path', ['--open' => true])
        ->expectsOutputToContain('Run config:setup to create it.')
        ->assertSuccessful();
});
