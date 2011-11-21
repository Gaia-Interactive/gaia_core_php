<?php
use Gaia\Test\Tap;

if( ! @fsockopen('127.0.0.1', '11211') ){
    Tap::plan('skip_all', 'couchbase not running on 127.0.0.1:11211');
}

if( ! @fsockopen('127.0.0.1', '5984') ){
    Tap::plan('skip_all', 'couchbase REST API not running on 127.0.0.1:5984');
}
