<?php
/**
 * EXPERIMENTAL.
 * This API could change dramatically over the next few cycles of iteration.
 * unstable, and for demonstration purposes only.
 */
namespace Gaia\Store;
use Gaia\Http;
use Gaia\Serialize\Json;
use Gaia\Container;
use Gaia\Exception;

class Couchbase extends Wrap {
    
    protected $resturl = '';
    protected $s;
    protected $design;
    protected $http;
    
    // a simple wrapper that matches on the design name prefix.
    const MAP_TPL = "function(doc){ if( doc._id.substr(0, %d) == '%s') { var inner = %s; inner(doc);}}";
    
    public function __construct($design,  $url = null, $core = null){
        $design = trim($design, '/ ');
        if( strlen( $design ) < 1 || ! preg_match('#^[a-z0-9_]+$#i', $design ) ) {
            throw new Exception('invalid design', $design );
        }
        $design .= '/';
        if( $url === NULL ) $url = 'http://127.0.0.1:5984/default/';
        $this->s = new Json('');
        $this->design = $design;
        if( ! $core instanceof Memcache ) $core = new Memcache( $core );
        $core = new Prefix( new Serialize($core, $this->s), $this->design);
        parent::__construct( $core );
        $this->resturl = $url;
    }
    
    public function getView( $view, $params = NULL ){
        $params = new Container( $params );
        $show_metadata = $params->show_metadata;
        unset( $params->show_metadata );
        if( ! $params->limit ) $params->limit = 10;
         if( ! $params->limit ) $params->skip = 0;
         if( ! $params->connection_timeout ) $params->connection_timeout = 60000;
        $url = $this->resturl;
        $http = $this->http = new Http\Request( $url . '_design/' . $this->design . '_view/' . $view . '/?' . http_build_query( $params->all()) );
        $http->serializer = $this->s;
        $response = $http->exec();
        if( $response->http_code != '200' ) throw new Exception('query failed', $http );
        if( ! is_array( $response->body ) ) throw new Exception('invalid response', $http );
        $rows = array();
        $len = strlen( $this->design );
        foreach($response->body['rows'] as $data ){
            $fields = array();
            if( isset( $data['id'] ) ) {
                $key = substr( $data['id'], $len );
            } else {
                $key = NULL;
            }
            if( is_array( $data['value'] ) ){
                foreach( $data['value'] as $k => $v ){
                    if( ! $show_metadata ){
                        $firstchar = substr($k, 0, 1);
                        if( $firstchar == '_' || $firstchar == '$' ) continue;
                    } else {
                        if( $k == '_id' ) $v = $key;
                    }
                    $fields[ $k ] = $v;
                }
            }
            if( $key === NULL ) {
                $rows[] = $fields;
            } else {
                $rows[ $key ] = $fields;
            }
        }
        return  array('total_rows'=> $response->body['total_rows'], 'rows'=>$rows );
    }
    
    public function createView($name, $map, $reduce ='' ){
        $url = $this->resturl;
        $http = $this->http = new Http\Request( $url . '_design/' . $this->design);
        $http->serializer = $this->s;
        $response = $http->exec();
        $result = $response->body;
        if( ! in_array( $response->http_code, array(200, 201, 404) )  ) throw new Exception('query failed', $http );
        if( ! is_array( $result ) ) $result = array();
        if( isset( $result['error'] ) ){
            if( $result['error']  == 'not_found' ){
                $result = array();
            } else {
                throw new Exception('query failed', $http );
            }
        }
        if( ! isset ( $result['views'] ) ) $result['views'] = array();
        if( $map === NULL ){
            unset( $result['views'][$name] );
        } else {
            $len = strlen( $this->design );
            if( $len > 0 ){
                $map = sprintf( self::MAP_TPL, $len, $this->design, $map );
            }
            $result['views'][$name] = array('map'=>$map);
            if( $reduce ) $result['views'][$name]['reduce'] = $reduce;
        }
        $http->post = $result;
        $http->method = 'PUT';
        $response = $http->exec();
        if( ! in_array( $response->http_code, array(200, 201) )  ) throw new Exception('query failed', $http );
        if( ! is_array( $response->body ) ) throw new Exception('invalid response', $http );
        return $response->body;
    }
    
    
     public function deleteAllViews(){
        $url = $this->resturl;
        $http = $this->http = new Http\Request( $url . '_design/' . $this->design);
        $http->serializer = $this->s;
        $response = $http->exec();
        $result = $response->body;
        if( $response->http_code == 404 ) return TRUE;
        if( $response->http_code !== 200 ) throw new Exception('query failed', $http );        
        $http->url = $http->url . '?rev=' . $result['_rev'];
        $http->method = 'DELETE';
        $response = $http->exec();
        if( ! in_array( $response->http_code, array(200, 201) )  ) throw new Exception('query failed', $http );
        if( ! is_array( $response->body ) ) throw new Exception('invalid response', $http );
        return $response->body;
    }

    
    
    public function deleteView( $name ){
        return $this->createView( $name, $map = NULL, $reduce = NULL );
    }
    
    public function flush(){
        
    }
    
    public function http(){
        return $this->http;
    }
}