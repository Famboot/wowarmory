<?php

$composer = `which composer`;
if (! $composer) {
    echo 'Composer needs to be installed on the system for the php shell to work';
    exit(1);

    // TODO Dynamically load composer if it is not available
    require 'phar://https://getcomposer.org/download/latest-stable/composer.phar';
}

function composerRequire(array $packages)
{
    // Define and may clear the php shell path
    $phpShellTempPath = '/var/tmp/php-shell/';
    if (in_array('--clear-php-shell-cache', $_SERVER['argv'])) {
        rrmdir($phpShellTempPath);
    }

    // Get the path for the executed file
    $callingFile = debug_backtrace()[0]['file'];
    $callingFileHash = md5($callingFile);
    $composerProjectPath = $phpShellTempPath.$callingFileHash;
    mkdir($composerProjectPath, recursive: true);

    // TODO Later maybe use this instead of the cli
    //$composer = new CompoundComposer($composerProjectPath, $packages);
    //$output = $composer->install();

    // Run composer install
    runComposerInit($composerProjectPath, $callingFileHash);
    foreach ($packages as $package) {
        runComposerRequire($package, $composerProjectPath);
    }

    require $composerProjectPath.'/vendor/autoload.php';
}

function runComposerInit(string $projectPath, string $projectId): void
{
    `cd $projectPath && composer init --name=php-shell/$projectId --no-interaction`;
}

function runComposerRequire(string $package, string $projectPath): void
{
    `cd $projectPath && composer require $package --no-interaction`;
}

// Got from https://stackoverflow.com/a/3338133
function rrmdir($dir): void
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != '.' && $object != '..') {
                if (is_dir($dir.DIRECTORY_SEPARATOR.$object) && ! is_link($dir.'/'.$object)) {
                    rrmdir($dir.DIRECTORY_SEPARATOR.$object);
                } else {
                    unlink($dir.DIRECTORY_SEPARATOR.$object);
                }
            }
        }
        rmdir($dir);
    }
}
