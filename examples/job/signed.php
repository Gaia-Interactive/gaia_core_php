#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Job;
use Gaia\Job\Runner;
use Gaia\Job\Config;
use Gaia\Pheanstalk;
use Gaia\Nonce;
$queue = 'signedtest';

set_time_limit(0);
if( ! @fsockopen('127.0.0.1', '11300')) {
    die("Beanstalkd not running on localhost\n");
}

if( ! @fsockopen('127.0.0.1', '11299')) {
    die("unable to connect to test job url\n");
}




$debugger = function ( $v ){
    if( $v instanceof Exception ) $v = $v->__toString();
    if( ! is_scalar( $v ) ) strval( $v );
    $dt =  "\n[" . date('H:i:s') . '] ';        
    echo( $dt . str_replace("\n", $dt, trim( $v )) );
};

$debugger( "\nInstantiating job runner ... \n");
$runner = new Runner();

$nonce = new Nonce('test001');

$config = Job::config();
//$config->addConnection( new Pheanstalk('127.0.0.1', '11300' ) );
$config->setQueuePrefix('test');
//$config->addQueueRate('test*',10);

// set up a connection pool for the default.
$conn_pool = array();

$config->setBuilder( function($job, array & $opts ) use ($nonce, & $conn_pool, $debugger) {
    $parts = new Gaia\Container( @parse_url( $job->url ));
    $uri = isset( $parts->path ) ? $parts->path : '/';
    if( $parts->query ) $uri = $uri . '?' . $parts->query;
    if( $job->id ) $opts[CURLOPT_HTTPHEADER][] = 'X-Job-Id: ' . $job->id;
    $opts[CURLOPT_HTTPHEADER][] = 'X-JOB-NONCE: ' . $nonce->create($uri, time() + 300 );
    
    if( $parts->host == '127.0.0.1' && $parts->port == '11299'){
        $conn = array_pop( $conn_pool );
        if( $conn ) {
            if( $conn['timeout'] < time() ) {
                curl_close( $conn['resource'] );
            } else {
                $job->persistent_timeout = $conn['timeout'];
                $job->resource = $conn['resource'];
            }
        }
        if( ! $job->persistent_timeout ){
            $job->persistent_timeout = time() + 60;
            $debugger( " ----------   NEW CONNECTION ---------------");
        }
        $opts[CURLOPT_HTTPHEADER][] = 'Connection: Keep-Alive';
        $opts[CURLOPT_HTTPHEADER][] = 'Keep-Alive: 120';
    }
});

$config->setHandler( function($job, $response ) use ($runner, $debugger, &$conn_pool) {
    //if( $response->headers->{'X-JOB-STATUS'} == 'complete') $job->flag = 1;
    if( $job->task == 'register'){
        Job::config()->registering = FALSE;
        $res = json_decode($response->body, TRUE);
        if( is_array( $res ) ){
            $config = Job::config();
            $existing_conns = array_keys( $config->connections() );
            if( $existing_conns ){
                $checksum = json_encode( $existing_conns );
                if($checksum != json_encode( $res['connections'] ) ){
                    $runner->shutdown();
                    Job::config()->setConnections( $res['connections'] );
                    $runner->process();
                }
            } else {
                Job::config()->setConnections( $res['connections'] );
            }
            Job::config()->setQueueRates( $res['queue_rates'] );
            Job::config()->setRetries( $res['retries'] );

        } else {
            $runner->shutdown();
        }
    }
    $request = $job;
    $info = $response;
    $out = "\nHTTP";
    if( $job->id ) $out .=" - " . $job->id;
    if( $info->http_code != 200 ) $out .= '-ERR';
    $out .= ": " . $info->url;
    if( $info->http_code == 200 ) {
        $debugger( $out );
        if( $job->persistent_timeout && $job->persistent_timeout > time() ){
            $conn_pool[] = array('timeout'=>$job->persistent_timeout, 'resource'=>$job->resource);
        } else {
            $job->close();
        }
        return;
    }
    
    $post = substr( (is_array( $job->post ) ? \Gaia\Http\Util::buildQuery( $job->post  ) : $job->post ),0, 75);
    $out .= '  ' . $post;
    if( strlen( $post )  >= 75 ) $out .= ' ...';
    if(  strlen( $info->response_header ) < 1 ) {
        $out .= " - NO RESPONSE";
    } else {
        $out .= "\n------\n";
        $out .= "\n" . $info->request_header;
        $out .= "\n" . $info->response_header . $info->body;
        $out .= "\n------\n";
    }
    $debugger( $out );
    
});


$register = function() use ( $runner ){
    $job = new Job( 'http://127.0.0.1:11299/job.php?register=1' );
    $job->ttr = 2;
    $job->task = 'register'; 
    $info = $runner->stats();
    $info['host'] = php_uname('n');
    if( function_exists('posix_getpid')) $info['pid'] = posix_getpid();
    $job->post = $info;
    return $job;
};


// run a check first to make sure which server to run.
$job = $register();
$job->run();

$runner->addTask( 
    $task = function () use ($runner, $register ){
        $config = Job::config();
        if( $config->registering ) return;
        $config->registering = TRUE;
        $runner->addJob( $register(), array(
                    CURLOPT_CONNECTTIMEOUT=>1, 
                    CURLOPT_HTTPHEADER =>array('Connection: Keep-Alive','Keep-Alive: 300')) );
    }
);




$ct = $runner->flush($queue);
$debugger( "JOBS flushed from the queue before starting: $ct");

$runner->addTask( function() use( $debugger, $queue, $runner ){
    static $ct;
    if( ! isset( $ct ) ) $ct = 0;
    $stats = $runner->stats();
    $diff = $ct - $stats['processed'];
    $iterations = 1000 - $diff;
    for( $i = 0; $i < $iterations; $i++){
        $job = new Job('http://127.0.0.1:11299/job.php?signed=1&ct=' . $ct . '&s='. microtime(TRUE) );
        $job->queue = $queue;
        $id = $job->store();
        $ct++;
        $debugger( sprintf("STORE - %s: %s",  $id, $job->url) );
    }
});



$runner->watch($queue);
$runner->setTimelimit(300);
$runner->setMax(10);
//$runner->attachDebugger( $debugger );
declare(ticks = 1);

$start = microtime(TRUE);

// signal handler function
$sig_handler = function ($signo) use ($runner, $start, $debugger, $start){

     switch ($signo) {
         case SIGTERM:
         case SIGINT:
         case SIGHUP:

             // handle shutdown tasks
             $debugger( "EXITING ... \nFinishing jobs in queue ...");
             sleep(1);
             $runner->shutdown();
             $elapsed = number_format( microtime(TRUE) - $start, 3);
             $s  = $runner->stats();
             $processed = $s['processed'];
             $debugger("$processed jobs processed in $elapsed secs");
             print "\n";
             exit;
             break;
         default:
             // handle all other signals
     }

};

if( function_exists('pcntl_signal')){

    $debugger( "Installing signal handler...");
    
    // setup signal handlers
    pcntl_signal(SIGTERM, $sig_handler);
    pcntl_signal(SIGINT, $sig_handler);
    pcntl_signal(SIGHUP,  $sig_handler);
}

$runner->process();

$elapsed = number_format( microtime(TRUE) - $start, 3);

$s  = $runner->stats();
$processed = $s['processed'];
$debugger("$processed jobs processed in $elapsed secs");
print "\n";
