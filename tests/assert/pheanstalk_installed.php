<?php
use Gaia\Test\Tap;

if( ! class_exists('Pheanstalk') ) {
    Tap::plan('skip_all', 'Pheanstalk class library not loaded. check vendors/pheanstalk.');
}
