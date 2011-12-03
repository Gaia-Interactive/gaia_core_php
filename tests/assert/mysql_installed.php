<?php
use Gaia\Test\Tap;

if( ! function_exists('mysql_connect') ){
    Tap::plan('skip_all', 'php-postgres not installed');
}
