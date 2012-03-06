#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Stream\Resource;

Tap::plan(6);

$read_ct = $write_ct = 0;
$read = function( Resource  $r ){
    $buf = fread($r->stream, 1024);
    if( $buf === FALSE || $buf === '' ) return FALSE;
    $r->in .= $buf;
    return TRUE;
};

$write = function( Resource  $r ){
    $len = strlen( $r->out );
    if( $len < 1 ) return FALSE;
    $fwlen = ( $len > 1024 ) ? 1024 : $len;
    $wlen = fwrite( $r->stream, $r->out, $fwlen );
    if( $wlen === FALSE ) return FALSE;
    if( $wlen < $len ) {
        $r->out = substr( $r->out, $wlen);
    } else {
        $r->out = '';
        rewind( $r->stream );
    }
    return TRUE;
};


$resource = new Resource( fopen('php://memory', 'r+b') );
$resource->write = $write;
$resource->read = $read;

$resource->out = $string = 'hello world' . "\n" . 'welcome to happy land';
$resource->write();
Tap::is( $resource->out, '', 'all of the data was written to the stream and removed from buffer');

$resource->read();

Tap::is( $resource->in, $string, 'data read from the string into the input buffer');


$seed = implode('', range('a', 'z')) . implode('', range('A', 'Z')) . implode('', range(0,9));
$seedlen = strlen( $seed ) - 1;
$longstring = '';
for( $i = 0; $i < 100000; $i++ ){
    $longstring .= $seed[mt_rand(0, $seedlen )];
}


$resource = new Resource( fopen('php://memory', 'r+b') );
$resource->write = $write;
$resource->read = $read;
$resource->out = $longstring;

while( $resource->write() );
Tap::is( $resource->out, '', 'sucked up a really long string into the stream');

while( $resource->read() );
Tap::is( $resource->in, $longstring, 'read it back from the stream' );

$utf8string = file_get_contents( __DIR__ . '/../sample/i_can_eat_glass.txt' );

$resource = new Resource( fopen('php://memory', 'r+b') );
$resource->write = $write;
$resource->read = $read;
$resource->out = $utf8string;

while( $resource->write() );
Tap::is( $resource->out, '', 'sucked up a utf8 string into the stream');

while( $resource->read() );
Tap::is( $resource->in, $utf8string, 'read it back from the stream' );

