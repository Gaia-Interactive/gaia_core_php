#!/usr/bin/env php
<?php
include __DIR__ . '/common.php';
include __DIR__ . '/../assert/pheanstalk_installed.php';
include __DIR__ . '/../assert/beanstalkd_running.php';

use Gaia\APN\Config;
use Gaia\APN\Queue;
use Gaia\APN\Runner;
use Gaia\APN\AppNotice;
use Gaia\APN\Connection;
use Gaia\Test\Tap;

$iterations = 2;

Tap::plan(5 + $iterations);


$config = Config::instance();
$config->addConnection($server = '127.0.0.1:11300');
$config->setQueuePrefix('apndebug');

$runner = new Runner($pool = new \Gaia\Stream\Pool);

$runner->attachDebugger( function( $data ){ Tap::debug( $data );});

Tap::ok( $runner instanceof Runner, 'instantiated the runner');

$connection = new Connection(fopen('php://temp', 'r+b') );
$connect_count = 0;
fwrite($connection->stream, pack('CCN', 8, 1, mt_rand(1, 1000000) ) );
rewind( $connection->stream );

$runner->attachStreamBuilder( function( $app ) use( $connection, & $connect_count) {
    $connect_count++;
    return $connection;
} );

$res = $runner->flush();
if( $res ) Tap::debug("flushed stale notices in the queue: $res");

$notices = array();

for( $i = 0; $i < $iterations; $i++){
    $notice = new AppNotice();
    $notice->setApp('test');
    $notice->setDeviceToken( $token = randomDeviceToken() );
    $notice->getMessage()->setText('microtime is ' . microtime(TRUE) );
    $id = Queue::store( $notice );
    $notices[ $id ] = $notice;
}

//Tap::debug( $notices );
$runner->setTimelimit(1);
$runner->setLimit( count($notices) + 1 );
$start = microtime(TRUE);
$runner->process();
$elapsed = number_format( microtime(TRUE) - $start, 3);


Tap::cmp_ok( $elapsed, '>', 1, "waited around for more notices to run but didn't find any (waited for $elapsed s)");

Tap::cmp_ok( $elapsed, '<', 4, "hit the timeout before too long");


Tap::debug( $stats = $runner->stats() );



Tap::cmp_ok( $stats['processed'], '>', $iterations - 1, 'processed the queue');
Tap::is( $stats['failed'], 1, 'read the simulated error');

rewind( $connection->stream );

$buf = fread( $connection->stream, 4098 );

foreach( $notices as $id => $notice ){
    Tap::ok( strpos( $buf, $notice->core()->serialize()) !== FALSE, 'found the notice in the stream output for id ' . $id);
}

