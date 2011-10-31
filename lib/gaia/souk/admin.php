<?php
namespace Gaia\Souk;

/**
* utility class to allow us to easily use SOUK without bothering with a user id.
* used for crons, and things of that sort.
*/
class Admin extends \Gaia\Souk {
    public function __construct( $app){
        parent::__construct( $app, $user_id = NULL );
    }
    public function user(){
        throw new Exception( get_class( $this ) . '::user() method not supported');
    }
}
