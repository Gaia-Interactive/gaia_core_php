<?php
use Gaia\Test\Tap;

if( ! @fsockopen('127.0.0.1', 11299) ){
    Tap::plan('skip_all', 'http://127.0.0.1:11299/ not started. run ./tests/webservice/start.sh');
}