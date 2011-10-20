#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Job;
use Gaia\Job\Runner;
use Gaia\Pheanstalk;

if( ! class_exists('Pheanstalk') ) {
    Tap::plan('skip_all', 'Pheanstalk class library not loaded. check vendors/pheanstalk.');
}

if( ! @fsockopen('127.0.0.1', '11300')) {
    Tap::plan('skip_all', 'Beanstalkd not running on localhost');
}

if( ! @fsockopen('127.0.0.1', '11299')) {
    Tap::plan('skip_all', "unable to connect to test job url: please run tests/webservice/start.sh\n");
}

Tap::plan(8);

$queue = 'runnertest';
$total_jobs = 10;
$runner = new Runner();

$config = Job::config();
$config->addConnection( new Pheanstalk('127.0.0.1', '11300' ) );
$config->setQueuePrefix('test');

$build_ct = 0;


$config->setBuilder( function($job, array & $opts ) use (& $build_ct) {
    $build_ct++;
});

$handle_ct = 0;

$config->setHandler( function($job, $response ) use ($runner, & $handle_ct) {
    $handle_ct++;
});

$runner->flush($queue);


for( $i = 0; $i < $total_jobs; $i++){
    $start = microtime(TRUE);
    $job = new Job('http://127.0.0.1:11299/');
    $job->queue = $queue;
    $id = $job->store();
    $elapsed = number_format( microtime(TRUE) - $start, 3);
}

$runner->watch($queue);
$runner->setTimelimit(1);
$runner->setMax(5);
$runner->setLimit( $expected_jobs = $total_jobs - 1 );
$start = microtime(TRUE);
$runner->process();
$stats = $runner->stats();
$elapsed = number_format( microtime(TRUE) - $start, 3);


Tap::is( $handle_ct, $expected_jobs, 'processed up to the limit of jobs');
Tap::is( $build_ct, $expected_jobs, 'only dequeued and built the jobs we needed up to the limit');
Tap::cmp_ok( $elapsed, '<', 3, "took less than 3 secs to run all the jobs we stored ( actual is $elapsed s)");
Tap::is( $stats['processed'], $expected_jobs, 'stats marked all the jobs as processed');
Tap::is( $stats['failed'], 0, 'no failures reported');
Tap::is( $stats['noreplies'], 0, 'no cases of no-reply reported');

$runner->flush($queue);

$runner->setLimit( $total_jobs + 1 );

$start = microtime(TRUE);
$runner->process();

$stats = $runner->stats();
$elapsed = number_format( microtime(TRUE) - $start, 3);


Tap::cmp_ok( $elapsed, '>', 1, "waited around for more jobs to run but didn't find any (waited for $elapsed s)");

Tap::cmp_ok( $elapsed, '<', 3, "hit the timeout before too long");


Tap::debug( $stats );
