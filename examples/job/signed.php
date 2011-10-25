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

$config->setBuilder( function($job, array & $opts ) use ($nonce) {
    $parts = new Gaia\Container( @parse_url( $job->url ));
    $uri = isset( $parts->path ) ? $parts->path : '/';
    if( $parts->query ) $uri = $uri . '?' . $parts->query;
    if( $job->id ) $opts[CURLOPT_HTTPHEADER][] = 'X-Job-Id: ' . $job->id;
    $opts[CURLOPT_HTTPHEADER][] = 'X-JOB-NONCE: ' . $nonce->create($uri, time() + 300 );
});

$config->setHandler( function($job, $response ) use ($runner, $debugger) {
    if( $response->headers->{'X-JOB-STATUS'} == 'complete') $job->flag = 1;
    if( $job->task == 'register'){
        Job::config()->registering = FALSE;
        $res = json_decode($response->body, TRUE);
        if( is_array( $res ) ){
            $config = Job::config();
            $existing_conns = array_keys( $config->connections() );
            if( $existing_conns ){
                $checksum = json_encode( $existing_conns );
                if($checksum != json_encode( $res['connections'] ) ){
                    var_dump( "checksum doesnt match: $checksum");
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
        return;
    }
    
    $post = substr( (is_array( $job->post ) ? http_build_query( $job->post  ) : $job->post ),0, 75);
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

for( $i = 0; $i < 1000; $i++){
    $start = microtime(TRUE);
    $job = new Job('http://127.0.0.1:11299/job.php?signed=1');
    $job->queue = $queue;
    $id = $job->store();
    $elapsed = number_format( microtime(TRUE) - $start, 3);
    $debugger( "STORE " . $id . ' ' . $elapsed . 's');
}

$runner->watch($queue);
$runner->setTimelimit(60);
$runner->setMax(10);
//$runner->attachDebugger( $debugger );
declare(ticks = 1);

// signal handler function
$sig_handler = function ($signo) use ($runner, $start, $debugger){

     switch ($signo) {
         case SIGTERM:
         case SIGINT:
         case SIGHUP:

             // handle shutdown tasks
             $debugger( "EXITING ... \nFinishing jobs in queue ...");
             sleep(1);
             $runner->shutdown();
             $elapsed = number_format( microtime(TRUE) - $start, 3);
             $debugger("DONE: $elapsed");
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

$start = microtime(TRUE);
$runner->process();

$elapsed = number_format( microtime(TRUE) - $start, 3);


$debugger( "\nDONE: $elapsed\n");
