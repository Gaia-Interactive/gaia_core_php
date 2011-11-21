<?php
/**
 * @copyright 2003-present GAIA Interactive, Inc.
 */
namespace Gaia\Store;
use Gaia\Http;
use Gaia\Serialize\Json;

class Couchbase extends Wrap {
    
    protected $url = '';
    protected $s;
    
    public function __construct( $core = null, $url = null ){
        $this->s = new Json('');
        if( ! $core instanceof Memcache ) $core = new Memcache( $core );
        $core = new Serialize($core, $this->s);
        parent::__construct( $core );
        $this->url = $url;
    }
    
    public function view( $name, $params = array() ){
        $url = $this->url;
        $http = new Http\Request( $url . '_design/' . $name . '/_view/' . $name . '?' . http_build_query( $params) );
        $http->serializer = $this->s;
        return $http->exec();
    }
}