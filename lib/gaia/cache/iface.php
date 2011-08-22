<?php
namespace Gaia\Cache;

interface Iface {
    public function add( $key, $value, $expires = 0);
    public function set( $key, $value, $expires = 0);
    public function replace( $key, $value, $expires = 0);
    public function increment( $key, $value = 1 );
    public function decrement( $key, $value = 1 );
    public function get( $input, $options = NULL );
}