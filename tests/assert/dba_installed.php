<?php
use Gaia\Test\Tap;
if( ! function_exists('dba_open') ) Tap::plan('skip_all', 'dba not enabled');
