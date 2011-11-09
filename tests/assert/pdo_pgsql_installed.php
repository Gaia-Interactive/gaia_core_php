<?php
use Gaia\Test\Tap;

if( ! in_array( 'pgsql', PDO::getAvailableDrivers()) ){
    Tap::plan('skip_all', 'this version of PDO does not support postgres');
}
