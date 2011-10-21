<?php
namespace Gaia;
Interface  StorageIface {
    public function set($name, $value);
    public function get($name);
    public function add( $key, $value );
    public function replace( $key, $value );
    public function increment( $key, $value = 1 );
    public function decrement( $key, $value = 1 );
    public function delete( $key );
    public function load( $input );
    public function __set( $k, $v );
    public function __get( $k );
    public function __unset( $k );
    public function __isset( $k );
}
