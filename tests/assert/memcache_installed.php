<?php
use Gaia\Test\Tap;

if( ! class_exists('Memcache') && ! class_exists('Memcached') ){
    Tap::plan('skip_all', 'no pecl-memcache or pecl-memcached extension installed');
}