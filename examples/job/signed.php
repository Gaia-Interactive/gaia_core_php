#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Job;
use Gaia\JobRunner;
use Gaia\Pheanstalk;
use Gaia\Nonce;

set_time_limit(0);
if( ! @fsockopen('127.0.0.1', '11300')) {
    Tap::plan('skip_all', 'Beanstalkd not running on localhost');
}

if( ! @fsockopen('127.0.0.1', '11299')) {
    Tap::plan('skip_all', 'unable to connect to test job url');
}

Tap::plan(5);

$tube = '__test__';

Job::attach( 
    function(){
        return array( new Pheanstalk('127.0.0.1', '11300' ) );
    }
);

for( $i = 0; $i < 1000; $i++){
    $start = microtime(TRUE);
    $job = new Job('http://127.0.0.1:11299/?t=' . time());
    $job->queue = 'signed';
    $id = $job->store();
    $elapsed = number_format( microtime(TRUE) - $start, 3);
    print "\nSTORE " . $id . ' ' . $elapsed . 's';
}

$start = microtime(TRUE);

$nonce = new Nonce('test001');


Job::watch('signed');
Job::config()->set('build', function($job, array & $opts ) use ($nonce) {
    $parts = new Gaia\Container( @parse_url( $job->url ));
    $uri = isset( $parts->path ) ? $parts->path : '/';
    if( $parts->query ) $uri = $uri . '?' . $parts->query;
    $opts[CURLOPT_HTTPHEADER][] = 'X-JOB-NONCE: ' . $nonce->create($uri, time() + 300 );
});

Job::config()->set('handle', function($job, $response ) {
    if( $response->headers->{'X-JOB-STATUS'} == 'complete') $job->flag = 1;
});

Job::config()->set('register_url', 'http://127.0.0.1:11299/?register');

print "\nHELLO\n";
$runner = new JobRunner();

$runner->setTimelimit(20);
$runner->enableDebug();
$runner->setDebugLevel(1);
$runner->setMax(10);
$runner->send();

$elapsed = number_format( microtime(TRUE) - $start, 3);


print "\nDONE: $elapsed\n";