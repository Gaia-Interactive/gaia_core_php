<?php
namespace Gaia\Store;

class Options extends Container {

    public function __construct( array $options ){
        $this->cache_missing = FALSE;
        $this->timeout = 0;
        if( isset( $options['missing_callback'] ) && is_callable( $options['missing_callback']) ) $this->missing_callback = $options['missing_callback'];
        if( isset( $options['response_callback'] ) && is_callable( $options['response_callback']) ) $this->response_callback = $options['response_callback'];
        if( isset( $options['prefix'] ) ) $this->prefix = $options['prefix'];
        if( isset( $options['timeout'] ) ) $this->timeout = $options['timeout'];
        if( isset( $options['default'] ) ) $this->default = $options['default'];
        if( isset( $options['cache_missing'] ) ) $this->default = $options['cache_missing'];
    }
}