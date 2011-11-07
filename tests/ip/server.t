#!/usr/bin/env php
<?php
namespace Gaia;

include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\IP;

$named_ips = array();
$_SERVER = array();

function gethostbyname( $n ){
    return isset( $GLOBALS['named_ips'][$n] ) ? $GLOBALS['named_ips'][$n] : FALSE;
}
$_SERVER = array();

Tap::plan(5);


Tap::is( IP::server(), '0.0.0.0' , 'when nothing returned from gethostbyname, returns empty ip' );

$named_ips = array(gethostname()=>'192.168.1.1');

Tap::is( IP::server(), '0.0.0.0' , 'server is cached' );

Tap::is( IP::server(TRUE), '192.168.1.1' , 'extracted server ip address from gethostbyname' );

$named_ips = array();
$_SERVER = array();

Tap::is( IP::server(), '192.168.1.1' , 'server is cached' );


$named_ips = array(gethostname()=>'192.168.1.1');
$_SERVER = array('SERVER_ADDR'=>'192.168.1.2');

Tap::is( IP::server(TRUE), '192.168.1.2' , 'prefers to get the value from server addr variable' );
