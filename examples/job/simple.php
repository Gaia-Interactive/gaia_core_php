#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Job;
use Gaia\JobRunner;
use Gaia\Pheanstalk;
set_time_limit(0);
if( ! @fsockopen('127.0.0.1', '11300')) {
    Tap::plan('skip_all', 'Beanstalkd not running on localhost');
}

if( ! @fsockopen('github.com', '443')) {
    Tap::plan('skip_all', 'unable to connect to test job url');
}

Tap::plan(5);

$tube = '__test__';

Job::attach( 
    function(){
        return array( new Pheanstalk('127.0.0.1', '11300' ) );
    }
);

for( $i = 0; $i < 100; $i++){
    $start = microtime(TRUE);
    $job = new Job('https://github.com/gaiaops/gaia_core_php/wiki');
    $job->queue = 'simple';
    $id = $job->store();
    $elapsed = number_format( microtime(TRUE) - $start, 3);
    print "\nSTORE " . $id . ' ' . $elapsed . 's';
}

$start = microtime(TRUE);


Job::watch('test');
//Job::config()->set('register_url', 'http://jloehrer.d.gaiaonline.com/test/dummy.php?register');

print "\nHELLO\n";
$runner = new JobRunner();

$runner->setTimelimit(20);
$runner->enableDebug();
$runner->setDebugLevel(1);
$runner->setMax(10);
$runner->send();

$elapsed = number_format( microtime(TRUE) - $start, 3);


print "\nDONE: $elapsed\n";