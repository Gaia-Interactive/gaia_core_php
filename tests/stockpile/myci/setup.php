<?php
use Gaia\Test\Tap;

include __DIR__ . '/../lib/setup.php';
include __DIR__ . '/../../assert/ci_installed.php';
include __DIR__ . '/../../assert/mysql_running.php';

Gaia\DB\Connection::load( array('test'=>function () {
    return new Gaia\DB( \DB( array(
                            'dbdriver'	=> 'mysql',
							'hostname'	=> '127.0.0.1',
							'database'	=> 'test') ) );
}
) );