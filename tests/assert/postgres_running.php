<?php
use Gaia\Test\Tap;

if( ! @fsockopen('127.0.0.1', 5432) ){
    Tap::plan('skip_all', 'postgres not running on 127.0.0.1:5432');
}
