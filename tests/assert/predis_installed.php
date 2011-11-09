<?php
use Gaia\Test\Tap;

if( ! class_exists('Predis\Client') ){
    Tap::plan('skip_all', 'Predis library not loaded. check vendor/predis.');
}