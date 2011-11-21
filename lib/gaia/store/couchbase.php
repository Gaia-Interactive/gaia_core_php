<?php
/**
 * @copyright 2003-present GAIA Interactive, Inc.
 */
namespace Gaia\Store;
use Gaia\Http;
use Gaia\Serialize\Json;
use Gaia\Container;
use Gaia\Exception;

class Couchbase extends Wrap {
    
    protected $url = '';
    protected $s;
    
    public function __construct(  $url = null,$core = null ){
        if( $url === NULL ) $url = 'http://127.0.0.1:5984/default/';
        $this->s = new Json('');
        if( ! $core instanceof Memcache ) $core = new Memcache( $core );
        $core = new Serialize($core, $this->s);
        parent::__construct( $core );
        $this->url = $url;
    }
    
    public function query( $design, $view, $params = NULL ){
        $params = new Container( $params );
        if( ! $params->limit ) $params->limit = 10;
         if( ! $params->limit ) $params->skip = 0;
         if( ! $params->connection_timeout ) $params->connection_timeout = 60000;
        $url = $this->url;
        $http = new Http\Request( $url . '_design/' . $design . '/_view/' . $view . '/?' . http_build_query( $params->all()) );
        $http->serializer = $this->s;
        $response = $http->exec();
        print $response;
        if( $response->http_code != '200' ) throw new Exception('query failed', $http );
        if( ! is_array( $response->body ) ) throw new Exception('invalid response', $http );
        return $response->body;

    }
}