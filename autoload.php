<?php

if( file_exists( __DIR__ . '/ENABLE_PHAR_GZ') ) {
    return include __DIR__ . '/autoload.phar.gz.php';
} elseif( file_exists( __DIR__ . '/ENABLE_PHAR' ) ){
    return include __DIR__ . '/autoload.phar.php';
} else {
    return include __DIR__ . '/autoload.lib.php';
}