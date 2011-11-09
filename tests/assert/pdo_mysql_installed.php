<?php
use Gaia\Test\Tap;

if( ! in_array( 'mysql', PDO::getAvailableDrivers()) ){
    Tap::plan('skip_all', 'this version of PDO does not support mysql');
}
