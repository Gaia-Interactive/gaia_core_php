<?php
use Gaia\Test\Tap;
if( ! function_exists('mb_check_encoding') ) {
    Tap::plan('skip_all', 'php5-mbstring not installed. compile php with --enable-mbstring');
}
