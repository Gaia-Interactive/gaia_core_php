<?php
use Gaia\Test\Tap;

if( ! class_exists('BaseFacebook') ){
    Tap::plan('skip_all', 'basefacebook class not loaded.');
}