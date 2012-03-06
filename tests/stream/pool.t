#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Stream\Resource;
use Gaia\Stream\Pool;

Tap::plan(5);

$pool = new Pool();

$read = function( Resource  $r ) use( $pool ){
    $buf = fread($r->stream, 1024);
    if( $buf === FALSE ) {
        var_dump( 'oooooooops');
        $pool->remove( $r );
        return FALSE;
    }
    
    $r->in .= $buf;
    if( $buf === '' ){
        if( $r->out || ! $r->has_read) return TRUE;
        $pool->remove( $r );
    }
    $r->has_read = TRUE;
    return TRUE;
};

$write = function( Resource  $r ) use ($pool ){
    $len = strlen( $r->out );
    if( $len < 1 ) return FALSE;
    $fwlen = ( $len > 1024 ) ? 1024 : $len;
    $wlen = fwrite( $r->stream, $r->out, $fwlen );
    if( $wlen === FALSE ){
        $pool->remove( $r );
        return FALSE;
    }
    if( $wlen < $len ) {
        $r->out = substr( $r->out, $wlen);
    } else {
        $r->out = '';
        rewind( $r->stream );
    }
    return TRUE;
};

$debug = function ( $message, $level ){
    echo "# " . date('H:i:s') . ' ' . $message . "\n";
};

//$pool->setDebugger( $debug );

$string = 'hello world' . "\n" . 'welcome to happy land';

$r1 = new Resource( fopen('php://temp', 'r+b') );
$r1->has_read = FALSE;
$r1->write = $write;
$r1->read = $read;
$r1->out = $string;
$pool->add( $r1 );


$r2 = new Resource( fopen('php://temp', 'r+b') );
$r2->has_read = FALSE;
$r2->write = $write;
$r2->read = $read;
$r2->out = $string;
$pool->add( $r2 );

$start = microtime(TRUE);
$pool->finish(1);

$elapsed = number_format(microtime(TRUE) - $start, 5, '.', '');

Tap::cmp_ok($elapsed, '<', 1, "finished reading/writing in less than a second. (actual: $elapsed s)");



Tap::is( $r1->out, '', 'wrote all the data for r1');
Tap::is( $r1->in, $string, 'read it back from the stream');

Tap::is( $r2->out, '', 'wrote all the data for r2');
Tap::is( $r2->in, $string, 'read it back from the stream');
