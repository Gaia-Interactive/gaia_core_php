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

if( ! @fsockopen('graph.facebook.com', '80')) {
    Tap::plan('skip_all', 'cant connect to test url');
}

Tap::plan(7);

$tube = '__test__';

Job::config()->addConnection( new Pheanstalk('127.0.0.1', '11300') );


$job = new Job('http://graph.facebook.com/cocacola');
$job->queue = 'test';
$start = microtime(TRUE);
$id = $job->store();
$elapsed = number_format( microtime(TRUE) - $start, 3);

Tap::ok( $id, 'job stored successfully');
Tap::like( $id, '/127.0.0.1:11300-[0-9]+/', 'returned a valid id');
Tap::cmp_ok($elapsed, '<', 1, "took less than 1 sec to store ( $elapsed )");

$res = $job->run();

Tap::ok($res, 'ran the job successfully');
Tap::is( $job->response->http_code, 200, 'returned code 200');
Tap::like( $job->response->body, '/coca-cola/i', 'got back the cocacola page');

$res = $job->complete();

Tap::ok( $res, 'marked the job as complete');