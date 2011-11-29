<?php
namespace Gaia\Http;
use Gaia\Container;
use Gaia\Exception;

// +---------------------------------------------------------------------------+
// | This file is part of the HTTP Framework.                                   |
// | Author 72squared  (john@gaiaonline.com)                                   |
// +---------------------------------------------------------------------------+

/*
* Use this class to run curl calls easily.
* $request = new \Gaia\Http\Request('http://news.yahoo.com/rss/');
* $request->post = array('param1'=>1, 'param2'=>2);
* $response = $request->exec();
* print PHP_EOL . "URL: " . $request->url;
* print PHP_EOL . "RESPONSE: " $response->body;
*/
class Request extends Container {
    
   /**
    * try to run the request right away.
    * @return Container of the response.
    * Usually this is the only method you need to know if you are using this object on its own.
    * Allows you to run a curl call and get response back.
    * If you need to do multiple calls in parallel, look at the pool class.
    */
    public function exec( array $opts = array() ){
        $ch = $this->build( $opts );
        $data = curl_exec( $ch );
        $info = curl_getinfo( $ch );
        if( ! is_array( $info ) ) $info = array();
        $info['response'] = $data;
        return $this->handle( $info );
    }

    
   /**
    * import data from a different http class into this one.
    * @param mixed      either an array or http object
    * @return void
    */
    public function load( $data ){
        if( is_string( $data ) && function_exists('json_decode') && ( $v = @json_decode( $data, TRUE ) ) ) $data = $v;
        if( is_object( $data ) ){
            foreach( array_keys($this->__d ) as $k ) $this->$k = ( isset( $data->$k ) ) ? $data->$k : NULL;
        }elseif( is_array( $data ) ){
            foreach(  array_keys($this->__d ) as $k ) $this->$k = ( isset( $data[$k] ) ) ? $data[$k] : NULL;
        } elseif( is_string( $data ) ){
            $this->url = $data;
        }
    }
        
   /**
    * utility method. send the Http request out through a stream and return the stream object
    * @param array    curl opts.
    */
    public function build( array $opts = array() ){
        // if no host is specified, try localhost loopback address.
        $url = substr( $this->url, 0, 1) == '/'  ? 'http://127.0.0.1' . $this->url : $this->url;
        
        // parse the url.
        $parts = @parse_url( $url );
        if( ! is_array( $parts ) ) $parts = array();
        $uri = isset( $parts['path'] ) ? $parts['path'] : '/';
        if( isset( $parts['query'] ) ) $uri .= '?' . $parts['query'];
        if( ! isset( $parts['host'] ) ) throw new Exception('invalid-uri');
        if( $this->handle && get_resource_type($this->handle) == 'curl' ){
            $ch = $this->handle;
        } else {
            $ch = $this->handle = curl_init();
        }
        if( ! isset($opts[CURLOPT_HTTPHEADER]) )$opts[CURLOPT_HTTPHEADER] = array();
        $headers = $this->headers;
        if( is_array( $headers ) || $headers instanceof iterator ){
            foreach( $headers as $k => $v ){
                if( is_int( $k ) ){
                    $opts[CURLOPT_HTTPHEADER][] = $v;
                } else {
                    $opts[CURLOPT_HTTPHEADER][] = $k . ': ' . $v;
                }
            }   
        }
        $opts[ CURLOPT_URL ] = $url;
        if( isset( $this->post ) ) {
            $opts[CURLOPT_POST] = 1;
            $s = $this->serializer;
            $post = $this->post;
            if( $s && $s instanceof \Gaia\Serialize\Iface ) {
                 $post = $s->serialize( $post );
                 $opts[CURLOPT_HTTPHEADER][] = 'X-Serialize: ' . str_replace('gaia\serialize\\', '',  strtolower(get_class( $s )));
            } else {
                if( is_array( $post ) ) $post = http_build_query( $post );
            }
            $opts[CURLOPT_POSTFIELDS] = $post;
        } 
        
        if ( $this->method ){
            if( isset( $opts[CURLOPT_POST]) ) unset( $opts[CURLOPT_POST] );
            $opts[CURLOPT_CUSTOMREQUEST] = strtoupper( $this->method );
        }
                
        if( isset( $opts[CURLOPT_POSTFIELDS] ) && substr( $opts[CURLOPT_POSTFIELDS], 0, 5 ) == '<?xml'){
           $opts[CURLOPT_HTTPHEADER][] = 'Content-Type: text/xml';
        } elseif( is_array( $this->post ) ) {
            $opts[CURLOPT_HTTPHEADER][] = 'application/x-www-form-urlencoded';
        }
        $opts[CURLOPT_RETURNTRANSFER] = 1;
        $opts[CURLINFO_HEADER_OUT] = 1;
        $opts[CURLOPT_HEADER] = 1;
        curl_setopt_array( $ch, $opts );
        return $ch;
    }
    
   /**
    * Handle the response ... internal method only. Used by the Pool class.
    */
    public function handle( array $info ){  
        if( ! isset( $info['http_code'] ) ) $info['http_code'] = 0;
        if( ! isset( $info['header_size'] ) ) $info['header_size'] = 0;
        $response_header =  substr( $info['response'], 0, $info['header_size'] );
        $header_lines = explode("\r\n", $response_header);
        $headers = self::parseHeaders($response_header );
        $info['body'] = substr( $info['response'], $info['header_size']);
        $s = $this->serializer;
        if( $s && $s instanceof \Gaia\Serialize\Iface ) {
            $info['body'] = $s->unserialize( trim($info['body']) );
        }
        $info['raw'] = $info['request_header'] . $info['response'];
        
        $info['headers'] = $info['response_headers'] = new \Gaia\Container($headers);
        $info['request_headers'] = new \Gaia\Container($info['request_header']);
        //$info['response_header'] = $response_header;
        unset( $info['response'] );
        unset( $info['request_header'] );
        $this->response = new \Gaia\Container( $info );
        return $this->response;
    }
    
    protected static function parseHeaders($headers) {
        $retVal = array();
        $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $headers));
        foreach( $fields as $field ) {
            if( ! preg_match('/([^:]+): (.+)/m', $field, $match) ) continue;
            $match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
            if( isset($retVal[$match[1]]) ) {
                $retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
            } else {
                $retVal[$match[1]] = trim($match[2]);
            }
          
        }
        return $retVal;
    }
    
    public function close(){
        if( $this->handle && get_resource_type($this->handle) == 'curl' ) curl_close( $this->handle );
        unset( $this->handle );
    }
    
    public function __destruct( ){
        $this->close();
    }
}
// EOC