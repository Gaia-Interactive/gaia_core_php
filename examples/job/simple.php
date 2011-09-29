#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Job;
use Gaia\JobRunner;
use Gaia\Pheanstalk;
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

Job::attach( 
    function(){
        return array( new Pheanstalk('127.0.0.1', '11300' ) );
    }
);

$ct = Job::flush($queue);

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


Job::watch($queue);

print "\nInstantiating job runner ... \n";
$runner = new JobRunner();

$runner->setTimelimit(20);
$runner->enableDebug();
$runner->setDebugLevel(1);
$runner->setMax(10);
$runner->send();

$elapsed = number_format( microtime(TRUE) - $start, 3);


print "\nDONE: $elapsed\n";