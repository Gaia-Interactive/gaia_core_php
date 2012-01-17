#!/usr/bin/env php
<?php
$DIR_VENDOR  = __DIR__ . '/../vendor';
$file =__DIR__ . '/gaia_core_php.phar';
@unlink($file . '.tar.gz');
$phar = new Phar($file);
$phar->buildFromDirectory(__DIR__ . '/../lib/');
$phar->convertToExecutable(Phar::TAR, Phar::GZ);


$file =__DIR__ . '/pheanstalk.phar';
@unlink($file . '.tar.gz');
$phar = new Phar($file);
$phar->buildFromDirectory( $DIR_VENDOR . '/pheanstalk/classes/');
$phar->convertToExecutable(Phar::TAR, Phar::GZ);


$file =__DIR__ . '/sfyaml.phar';
@unlink($file . '.tar.gz');
$phar = new Phar($file);
$phar->buildFromDirectory( $DIR_VENDOR .  '/yaml/lib/');
$phar->convertToExecutable(Phar::TAR, Phar::GZ);


$file =__DIR__ . '/predis.phar';
@unlink($file . '.tar.gz');
$phar = new Phar($file);
$phar->buildFromDirectory($DIR_VENDOR . '/predis/lib/');
$phar->convertToExecutable(Phar::TAR, Phar::GZ);

$file =__DIR__ . '/facebook.phar';
@unlink($file . '.tar.gz');
$phar = new Phar($file);
$phar->buildFromDirectory($DIR_VENDOR . '/facebook/php-sdk/src/');
$phar->convertToExecutable(Phar::TAR, Phar::GZ);
