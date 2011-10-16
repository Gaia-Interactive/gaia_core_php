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
* $response = $request->exec();
* 
*
*
*/
class Request extends Container implements \Iterator {
    
   /**
    * try to run the request right away.
    * @return Container of the response.
    */
    public function exec( array $opts = array() ){
        $ch = $this->build( $opts );
        return $this->handle( curl_exec($ch), curl_getinfo($ch));
    }

    
   /**
    * import job data from a different http class into this one.
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
        $url = substr( $this->url, 0, 1) == '/'  ? 'http://127.0.0.1' . $this->url : $this->url;
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
        $opts[ CURLOPT_URL ] = $url;
        if( isset( $parts['user'] ) && isset( $parts['pass'] ) ){
            $opts[CURLOPT_USERPWD] = $parts['user'].':'.$parts['pass'];
        }

        if( $this->post ) {
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = is_array( $this->post ) ? http_build_query( $this->post  ) : $this->post;
        }

        if( ! isset($opts[CURLOPT_HTTPHEADER]) )$opts[CURLOPT_HTTPHEADER] = array();
                
        if( isset( $opts[CURLOPT_POSTFIELDS] ) && substr( $opts[CURLOPT_POSTFIELDS], 0, 5 ) == '<?xml'){
           $opts[CURLOPT_HTTPHEADER][] = 'Content-Type: text/xml';
        } else {
            $opts[CURLOPT_HTTPHEADER][] = 'application/x-www-form-urlencoded';
        }
        $opts[CURLOPT_FOLLOWLOCATION] = 1;
        if( substr($url, 0, 5) == 'https' ){
            $opts[CURLOPT_SSL_VERIFYPEER] = 0;
            $opts[CURLOPT_SSL_VERIFYHOST] = 0;
        }
        $opts[CURLOPT_RETURNTRANSFER] = 1;
        $opts[CURLINFO_HEADER_OUT] = 1;
        $opts[CURLOPT_HEADER] = 1;
        curl_setopt_array( $ch, $opts );
        return $ch;
    }
    
    public function handle( $curl_data, $curl_info ){  
        if( ! is_array( $curl_info ) ) $curl_info = array();
        if( ! isset( $curl_info['http_code'] ) ) $curl_info['http_code'] = 0;
        if( ! isset( $curl_info['header_size'] ) ) $curl_info['header_size'] = 0;
        $response_header =  substr( $curl_data, 0, $curl_info['header_size'] );
        $header_lines = explode("\r\n", $response_header);
        $headers = array();
        foreach( $header_lines as $line ){
            if( ! strpos( $line, ':') ) continue;
            list( $k, $v ) = explode(':', $line );
            $k = trim( $k );
            $v = trim( $v );
            $headers[ $k ] = $v;
        }
        $body = substr( $curl_data, $curl_info['header_size']);
        $curl_info['headers'] = new \Gaia\Container($headers);
        $curl_info['response_header'] = $response_header;
        $curl_info['body'] = $body;
        $this->response = new \Gaia\Container( $curl_info );
        return $this->response;
    }
}
// EOC