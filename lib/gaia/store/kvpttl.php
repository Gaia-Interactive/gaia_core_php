<?php
namespace Gaia\Store;

class KvpTTL extends EmbeddedTTL {
    public function __construct( $input = NULL ){
        parent::__construct( new KVP( $input ) );
    }
}