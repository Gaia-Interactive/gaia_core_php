<?php
use Gaia\Test\Tap;
if( ! ini_get('date.timezone') ) {
    Tap::plan('skip_all', "php ini date.timezone not set.See http://www.php.net/manual/en/function.date-default-timezone-get.php");
}
