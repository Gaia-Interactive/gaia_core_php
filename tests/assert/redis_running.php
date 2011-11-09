<?php
use Gaia\Test\Tap;

if( ! @fsockopen('127.0.0.1', '6379')) {
    Tap::plan('skip_all', 'Redis not running on localhost');
}
