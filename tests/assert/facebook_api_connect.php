<?php
use Gaia\Test\Tap;

if( ! @fsockopen('api.facebook.com', '443') ){
    Tap::plan('skip_all', 'unable to connect to facebook api');
}
