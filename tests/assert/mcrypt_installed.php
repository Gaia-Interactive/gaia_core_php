<?php
use Gaia\Test\Tap;
if( ! function_exists('mcrypt_encrypt')) {
    Tap::plan('skip_all', 'php5-mcrypt extension not installed');
}
                    