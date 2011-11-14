<?php
use Gaia\Test\Tap;

if( ! @fsockopen('127.0.0.1', '11211') ){
    Tap::plan('skip_all', 'memcache not running on 127.0.0.1:11211');
}
