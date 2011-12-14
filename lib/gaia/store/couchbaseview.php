<?php

namespace Gaia\Store;
use Gaia\Http;
use Gaia\Container;
use Gaia\Exception;

class CouchbaseView {
    
    protected $rest = '';
    protected $app = '';
    protected $http;
    
    // a simple wrapper that matches on the design name prefix.
    const MAP_TPL = "
    function(doc){ 
        if( doc._id.substr(0, %d) == '%s') { 
            var d = eval(uneval(doc));
            d._id = doc._id.substr(%d);
            var __inner = %s; 
            __inner(d);  
        }
    }";
    
    
    
    /**
    * Instantiate the couchbase object. pass in a set of named params.
    * Example:
    *      $cb = new Couchbase( array(
    *                      'app'       => 'myapp',
    *                      'rest'      => 'http://127.0.0.1:8092/default/',
    *                      'socket'    => '127.0.0.1:11211',
    *              ));
    */
    public function __construct( $rest, $app = '' ){
        $this->rest = $rest;
        $this->app = $app;
    }
    
    /**
    * get back a the view results.
    * Example:
    *    $params = array(
    *       'startkey'=>'bear',
    *       'endkey'=>'zebra',
    *       'connection_timeout'=> 60000,
    *       'limit'=>10,
    *       'skip'=>0,
    *       'full_set'=>'true',
    *    );
    *    $result = $view->query('mammals' $params);
    *
    */
    public function query( $view, $params = NULL ){
        $params = new Container( $params );
        foreach( $params as $k => $v ) {
            if( ! is_scalar( $v ) || preg_match('#key#i', $k) ){
                $params->$k = json_encode( $v );
            }
        }
        $len = strlen( $this->app );
        $app = ( $len > 0 ) ? $this->app : 'default/';
        $http = $this->request( '_design/' . $app . '_view/' . $view . '/?' . \Gaia\Http\Util::buildQuery( $params->all()) );
        $response = $this->validateResponse( $http->exec(), array(200) );
        $result = $response->body;
        if( $len < 1 ) return $result;
        foreach($result['rows'] as & $row ){
            if( isset( $row['id'] ) ) {
                $row['id'] = substr( $row['id'], $len );
            }
        }
        return $result;
    }
 /*
    * read a view's info
    * returns an ok status along with a document rev id.
    */
    public function get( $name ){
        $len = strlen( $this->app );
        $app = ( $len > 0 ) ? $this->app : 'default/';
        $http = $this->request( '_design/' . $app);
        $response = $this->validateResponse( $http->exec(), array(200,404) );
        $result = $response->body;
        if( ! isset( $result['views'] ) ) return FALSE;
        if( ! isset( $result['views'][ $name ] ) ) return FALSE;
        if( ! isset( $result['views'][ $name ]['map'] ) ) return FALSE;
        $pattern = "#var[\s]__inner[\s]\=[\s]([\s\S]+);[\s]+__inner\(d\);#";
        
        if( preg_match($pattern, $result['views'][$name]['map'], $matches ) )  {
            $result['views'][$name]['map'] = $matches[1];
        }
        return $result['views'][$name];
    }
    

    
    /*
    * create or overwrite a view.
    * Example:
    *
    *    $res = $view->set('amount', array('map'=>'function(doc){ emit(doc._id, doc.amount);}', 'reduce'=>'_sum'));
    *
    * This uses the built-in _sum reduce function that can give you the sum of all the results.
    * or just specify a map function:
    *
    *    $res = $view->set('full', array('map'=>'function(doc){ emit(doc._id, {foo: doc.foo});}'));
    *
    * returns an ok status along with a document rev id.
    */
    public function set($name, array $view = NULL ){
        $len = strlen( $this->app );
        $app = ( $len > 0 ) ? $this->app : 'default/';
        $http = $this->request( '_design/' . $app);
        $response = $this->validateResponse( $http->exec(), array(200, 201, 404) );
        $result = $response->body;
        if( ! is_array( $result ) ) $result = array();
        if( isset( $result['error'] ) ){
            if( $result['error']  == 'not_found' ){
                $result = array();
            } else {
                throw new Exception('query failed', $http );
            }
        }
        if( ! isset ( $result['views'] ) ) $result['views'] = array();
        if( $view === NULL ){
            unset( $result['views'][$name] );
        } else {
            if( isset( $view['map'] ) && $len ) $view['map'] = sprintf( self::MAP_TPL, $len, $this->app, $len, $view['map'] );
            $result['views'][$name] = $view;
        }
        $result['views'] = (object) $result['views'];
        $http->post = json_encode( (object) $result);
        $http->method = 'PUT';
        $response = $this->validateResponse( $http->exec(), array(200, 201) );
        return $response->body;
    }
    
    /**
    * deletes all of the views created in a given app namespace.
    */
    public function flush(){
        $len = strlen( $this->app );
        $app = ( $len > 0 ) ? $this->app : 'default/';
        $http = $this->request( '_design/' . $app);
        $response = $this->validateResponse( $http->exec(), array(404, 200) );
        $result = $response->body;
        if( $response->http_code == 404 ) return TRUE;
        $http->url = $http->url . '?rev=' . $result['_rev'];
        $http->method = 'DELETE';
        $response = $this->validateResponse( $http->exec(), array(200, 201) );
        return $response->body;
    }

    /**
    * deletes a specific view in a given app namespace.
    * Example:
    *
    *   $view->delete('amount');
    *
    * throws an exception on error, returns an ok status and rev id on success.
    */
    public function delete( $name ){
        return $this->set( $name, $view = NULL );
    }
    
    /*
    * temporary debug method, do not use.
    */
    public function http(){
        return $this->http;
    }
    
    /*
    * temporary debug method, do not use.
    */
    public function request($path){
        $http = $this->http = new Http\Request( $this->rest . $path );
        $http->handle = function ( $response ){
            $body = @json_decode( $response->body, TRUE );
            if( is_array( $body ) ) $response->body = $body;
        };
        return $http;
    }
    
    /**
    * handle a response.
    */
    protected function validateResponse( $response, array $allowed_codes ){
        if( ! in_array( $response->http_code, $allowed_codes )  ) throw new Exception('query failed', $response );
        if( ! is_array( $response->body ) ) throw new Exception('invalid response', $response );
        return $response;
    }
}