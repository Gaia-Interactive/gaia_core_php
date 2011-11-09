<?php
use Gaia\Test\Tap;

if( ! function_exists('curl_init') ){
    Tap::plan('skip_all', 'php curl library not installed');
}
