#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Cache;
use Gaia\Store;

# make sure all the stub classes work
Tap::plan(17);
Tap::ok( new Cache\APC() instanceof Store\APC, 'apc inherited from store');
Tap::ok( new Cache\Callback( new Store\KVP, function(){}) instanceof Store\Callback, 'callback inherited from store');
Tap::ok( new Cache\Disabled() instanceof Store\Disabled, 'disabled inherited from store');
Tap::ok( new Cache\Floodcontrol(new Store\KVP, array('scope'=>'test')) instanceof Store\FloodControl, 'floodcontrol inherited from store');
Tap::ok( new Cache\Gate( new Store\KVP ) instanceof Store\Gate, 'gate inherited from store');
if( class_exists('memcache') || class_exists('memcached')){
    Tap::ok( new Cache\Memcache instanceof Store\Memcache, 'memcache inherited from store');
} else {
    Tap::pass('skipping memcache test because php extension not loaded');
}
Tap::ok( new Cache\Mock instanceof Store\Mock, 'mock inherited from store');
Tap::ok( new Cache\Observe(new Store\KVP ) instanceof Store\Observe, 'observe inherited from store');
Tap::ok( new Cache\Options( array() ) instanceof Store\Options, 'options inherited from store');
Tap::ok( new Cache\Prefix( new Store\KVP, 'test') instanceof Store\Prefix, 'prefix inherited from store');
if( class_exists('Predis\Client') ){
    Tap::ok( new Cache\Redis() instanceof Store\Redis, 'redis inherited from store');
} else {
    Tap::pass('skipping redis test ... predis library not loaded');
}
Tap::ok( new Cache\Replica(array(new Store\KVP)) instanceof Store\Replica, 'replica inherited from store');
Tap::ok( new Cache\Revision(new Store\KVP) instanceof Store\Revision, 'revision inherited from store');
Tap::ok( new Cache\Session(new Store\KVP) instanceof Store\Session, 'session inherited from store');
Tap::ok( new Cache\Stack(new Store\KVP) instanceof Store\Stack, 'stack inherited from store');
Tap::ok( new Cache\Wrap(new Store\KVP) instanceof Store\Wrap, 'wrap inherited from store');
if( class_exists('BaseFacebook') ){
    Tap::ok( new Cache\Facebook(new \Gaia\Facebook\NoAuth, new Store\KVP) instanceof Gaia\Facebook\APICache, 'facebook inherited from apicache');
} else {
    Tap::pass('skipping facebook check ... basefacebook class not loaded');
}