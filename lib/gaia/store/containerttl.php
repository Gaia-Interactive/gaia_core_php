<?php
namespace Gaia\Store;

class ContainerTTL extends EmbeddedTTL {
    function __construct(){
        parent::__construct( new Internal );
    }
}