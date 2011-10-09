#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Job;
use Gaia\JobRunner;
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

Job::attach( 
    function(){
        return array( new Pheanstalk('127.0.0.1', '11300' ) );
    }
);

$ct = Job::flush($queue);

print "\nJOBS flushed from the queue before starting: $ct\n";



for( $i = 0; $i < 1000; $i++){
    $start = microtime(TRUE);
    $job = new Job('http://127.0.0.1:11299/?signed=1');
    $job->queue = $queue;
    $id = $job->store();
    $elapsed = number_format( microtime(TRUE) - $start, 3);
    print "\nSTORE " . $id . ' ' . $elapsed . 's';
}

$start = microtime(TRUE);

$nonce = new Nonce('test001');


Job::watch($queue);
Job::config()->set('build', function($job, array & $opts ) use ($nonce) {
    $parts = new Gaia\Container( @parse_url( $job->url ));
    $uri = isset( $parts->path ) ? $parts->path : '/';
    if( $parts->query ) $uri = $uri . '?' . $parts->query;
    $opts[CURLOPT_HTTPHEADER][] = 'X-JOB-NONCE: ' . $nonce->create($uri, time() + 300 );
});

Job::config()->set('handle', function($job, $response ) {
    if( $response->headers->{'X-JOB-STATUS'} == 'complete') $job->flag = 1;
});

Job::config()->set('register_url', 'http://127.0.0.1:11299/?register=1');

print "\nInstantiating job runner ... \n";
$runner = new JobRunner();

$runner->setTimelimit(20);
$runner->enableDebug();
$runner->setDebugLevel(1);
$runner->setMax(10);

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

    echo "Installing signal handler...\n";
    
    // setup signal handlers
    pcntl_signal(SIGTERM, $sig_handler);
    pcntl_signal(SIGINT, $sig_handler);
    pcntl_signal(SIGHUP,  $sig_handler);
}


$runner->send();

$elapsed = number_format( microtime(TRUE) - $start, 3);


print "\nDONE: $elapsed\n";