<?php
use Gaia\Test\Tap;

if( ! in_array('ssl', stream_get_transports()) ){
    Tap::plan('skip_all', 'php ssl socket transport not installed');
}
