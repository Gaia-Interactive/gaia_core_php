<?php
use Gaia\Test\Tap;

if( ! @fsockopen('127.0.0.1', '11300')) {
    Tap::plan('skip_all', 'Beanstalkd not running on localhost');
}
