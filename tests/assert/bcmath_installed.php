<?php
use Gaia\Test\Tap;
if( ! function_exists('bcadd') ) {
    Tap::plan('skip_all', 'php5-bcmath extension not installed');
}
