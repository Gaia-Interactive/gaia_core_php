#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Job;
use Gaia\Job\Runner;
use Gaia\Job\Config;
use Gaia\Pheanstalk;
use Gaia\Nonce;
use Gaia\Debugger;
$queue = 'signedtest';

set_time_limit(0);
if( ! @fsockopen('127.0.0.1', '11300')) {
    die("Beanstalkd not running on localhost\n");
}

if( ! @fsockopen('127.0.0.1', '11299')) {
    die("unable to connect to test job url\n");
}






print "\nInstantiating job runner ... \n";
$runner = new Runner();
$debugger = new Debugger;
$nonce = new Nonce('test001');

$config = Job::config();
$config->addConnection( new Pheanstalk('127.0.0.1', '11300' ) );
$config->setQueuePrefix('test');


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
        $res = json_decode($response->body);
        if( ! is_array( $res ) ){
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
        $debugger->render( $out );
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
    $debugger->render( $out );
    
});

$config->set('register_url', 'http://127.0.0.1:11299/?register=1');



$runner->addTask( 
    function (Runner $runner){
        $config = Job::config();
        if( $config->registering ) return;
        $config->registering = TRUE;
        $job = new Job( 'http://127.0.0.1:11299/?register=1' );
        $job->ttr = 2;
        $job->task = 'register'; 
        $info = $runner->stats();
        $info['host'] = php_uname('n');
        $info['pid'] = posix_getpid();
        $job->post = $info;
        $runner->addJob( $job, array(
                    CURLOPT_CONNECTTIMEOUT=>1, 
                    CURLOPT_HTTPHEADER =>array('Connection: Keep-Alive','Keep-Alive: 300')) );
    }
);



$ct = $runner->flush($queue);
print "\nJOBS flushed from the queue before starting: $ct\n";



for( $i = 0; $i < 5000; $i++){
    $start = microtime(TRUE);
    $job = new Job('http://127.0.0.1:11299/?signed=1');
    $job->queue = $queue;
    $id = $job->store();
    $elapsed = number_format( microtime(TRUE) - $start, 3);
    print "\nSTORE " . $id . ' ' . $elapsed . 's';
}

$runner->watch($queue);
$runner->setTimelimit(120);
$runner->setMax(10);
//$runner->attachDebugger( $debugger );
declare(ticks = 1);

// signal handler function
$sig_handler = function ($signo) use ($runner, $start){

     switch ($signo) {
         case SIGTERM:
         case SIGINT:
         case SIGHUP:

             // handle shutdown tasks
             echo "\nEXITING ... \nFinishing jobs in queue ...\n";
             sleep(1);
             $runner->shutdown();
             $elapsed = number_format( microtime(TRUE) - $start, 3);
             print "\nDONE: $elapsed\n";
             exit;
             break;
         default:
             // handle all other signals
     }

};

if( function_exists('pcntl_signal')){

    echo "\nInstalling signal handler...\n";
    
    // setup signal handlers
    pcntl_signal(SIGTERM, $sig_handler);
    pcntl_signal(SIGINT, $sig_handler);
    pcntl_signal(SIGHUP,  $sig_handler);
}

$start = microtime(TRUE);
$runner->process();

$elapsed = number_format( microtime(TRUE) - $start, 3);


print "\nDONE: $elapsed\n";