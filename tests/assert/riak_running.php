<?php
use Gaia\Test\Tap;

if( ! @fsockopen('127.0.0.1', '8098')) {
    Tap::plan('skip_all', 'Riak not running on localhost');
}
