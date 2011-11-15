<?php
use Gaia\Test\Tap;
if( ! class_exists('domdocument') ) {
    Tap::plan('skip_all', 'php dom classes missing. install php5-xml.');
}
