#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Job;
use Gaia\Job\Runner;
use Gaia\Job\Config;
use Gaia\Pheanstalk;
use Gaia\Http;

set_time_limit(0);

$queue = 'simple';

$urls = array(
    'http://127.0.0.1:11299/',
    'https://github.com:443/gaiaops/gaia_core_php/wiki',
);


if( ! @fsockopen('127.0.0.1', '11300')) {
    Tap::plan('skip_all', 'Beanstalkd not running on localhost');
}

$status = FALSE;
foreach( $urls as $url ){
    $u = new Gaia\Container( parse_url( $url ) );
    if( ! @fsockopen($u->host, $u->port)) {
        continue;
    }
    $status = TRUE;
    break;
}

if( ! $status ){
    Tap::plan('skip_all', 'unable to connect to test job url: ' . $url);
}

Tap::plan(5);

$tube = '__test__';

Job::config()->addConnection( new Pheanstalk('127.0.0.1', '11300') );
Job::config()->setQueuePrefix('test');

$runner = new Runner();
$ct = $runner->flush($queue);

print "\nJOBS flushed from the queue before starting: $ct\n";

for( $i = 0; $i < 100; $i++){
    $start = microtime(TRUE);
    $job = new Job($url);
    $job->queue = $queue;
    $id = $job->store();
    $elapsed = number_format( microtime(TRUE) - $start, 3);
    print "\nSTORE " . $id . ' ' . $elapsed . 's';
}

$start = microtime(TRUE);


$runner->watch($queue);
$runner->setTimelimit(20);
$runner->setMax(10);

$runner->attachDebugger( $debugger = function ( $v ){
    if( $v instanceof Exception ) $v = $v->__toString();
    if( ! is_scalar( $v ) ) strval( $v );
    $dt =  "\n[" . date('H:i:s') . '] ';        
    echo( $dt . str_replace("\n", $dt, trim( $v )) );
});
$debugger("kicking off job runner ...\n---------------------");
Job::config()->setHandler( 
    function( Http\Request $request ) use( $debugger ){
        $out = "\nHTTP";
        if( $request->id ) $out .=" - " . $request->id;
        $info = $request->response;
        if( $info->http_code != 200 ) $out .= '-ERR';
        $out .= ": " . $info->url;
        $post = substr( (is_array( $request->post ) ? http_build_query( $request->post  ) : $request->post ),0, 75);
        $out .= '  ' . $post;
        if( strlen( $post )  >= 75 ) $out .= ' ...';
        if(  strlen( $info->raw ) < 1 ) {
            $out .= " - NO RESPONSE";
        } else {
            $out .= "\n------\n";
            $out .= "\n" . $info->raw;
            $out .= "\n------\n";
        }
        $debugger( $out );
    }
);

$runner->process();

$elapsed = number_format( microtime(TRUE) - $start, 3);


print "\nDONE: $elapsed\n";