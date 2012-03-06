#!/usr/bin/env php
<?php
include __DIR__ . '/common.php';
include __DIR__ . '/../assert/pheanstalk_installed.php';
include __DIR__ . '/../assert/beanstalkd_running.php';

use Gaia\APN\Config;
use Gaia\APN\Queue;
use Gaia\APN\AppNotice;
use Gaia\Test\Tap;

Tap::plan(3);

$config = Config::instance();
$config->addConnection($server = '127.0.0.1:11300');
$config->setQueuePrefix('apntest1');

$notice = new AppNotice();
$notice->setApp('queuetest');
$notice->setDeviceToken( $token = randomDeviceToken() );

$id = Queue::store( $notice );
Tap::like( $id, '#' . preg_quote( $server . '-', '#' ) . '\d+$#', 'put a notice in the queue, got the id back');

$res = Queue::find( $id );

//Tap::debug( $res );

//Tap::debug( $notice );

Tap::is( print_r( $res, TRUE), print_r( $notice, TRUE), 'stored and retrieved a notice');

//$res = Queue::fail( $id );


$res = Queue::remove( $id );



$e = null;
try { 
    Queue::find( $id );
} catch( Exception $e ){
    $e = $e->__toString();
}

Tap::like($e, '#not_found#i', 'after deleting, get message not found');

