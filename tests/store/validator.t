#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Store;

$key_check = function( $key ){
    return is_scalar( $key ) ? TRUE : FALSE;
};

$multi_key_check = function( $input ) use ( $key_check ){
    if( $key_check( $input ) ) return TRUE;
    if( is_array( $input ) ){
        foreach( $input as $key ) {
            if( ! $key_check( $key ) ) return FALSE;
        }
        return TRUE;
    }
    return FALSE;
};

$validators = array(
    'add'=> $key_check,
    'replace'=> $key_check,
    'set'=> $key_check,
    'delete'=>$key_check,
    'increment'=>$key_check,
    'decrement'=>$key_check,
    'get'=>$multi_key_check,
    

);

$cache = new Store\Validator(new Store\KvpTTL(), $validators);
include __DIR__ . '/generic_tests.php';