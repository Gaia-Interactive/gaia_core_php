<?php
namespace Gaia\Http;
use Gaia\Container;
use Gaia\Exception;

// +---------------------------------------------------------------------------+
// | This file is part of the Job Framework.                                   |
// | Author 72squared  (john@gaiaonline.com)                                   |
// +---------------------------------------------------------------------------+


class Request extends Container implements \Iterator {

   /**
    * Internal variables.
    *
    */
    protected $__d = array(
                    'url'=>'',
                    'post'=>'',
                    'response'=>NULL,
                    'proxyhost'=>FALSE,
    );
    
   /**
    * try to run the job right away.
    * @param int    how many seconds to wait on i/o before giving up
    * @return boolean
    */
    public function exec( array $opts = array(), array $headers = array() ){
        $ch = $this->build( $opts, $headers );
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
    public function build( array $opts = array(), array $headers = array() ){
        $url = substr( $this->url, 0, 1) == '/'  ? 'http://127.0.0.1' . $this->url : $this->url;
        
        $parts = @parse_url( $url );
        if( ! is_array( $parts ) ) $parts = array();
        $uri = isset( $parts['path'] ) ? $parts['path'] : '/';
        if( isset( $parts['query'] ) ) $uri .= '?' . $parts['query'];
        if( ! isset( $parts['host'] ) ) throw new Exception('invalid-uri');
        $ch = curl_init($url);
        if( ! empty( $opts ) ) curl_setopt_array( $ch, $opts );
        $headers += array(
                    'Connection: Keep-Alive',
                    'Keep-Alive: 300',
                    'Accept-Charset: ISO-8859-1,utf-8',
                    'Accept-language: en-us',
                    'Accept: text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,image/jpeg,image/gif,*/*',
        );
        
        if( isset( $parts['user'] ) && isset( $parts['pass'] ) ){
            curl_setopt($ch , CURLOPT_USERPWD,$parts['user'].':'.$parts['pass']);
        }

        if( $this->post ) {
            curl_setopt( $ch, CURLOPT_POST, 1);
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $this->post);
        }
        
        if( substr( $this->post, 0, 5 ) == '<?xml'){
           $headers[] = 'Content-Type: text/xml';
        } else {
            $headers[] = 'application/x-www-form-urlencoded';
        }
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
        if( $this->proxyhost ){
            curl_setopt( $ch, CURLOPT_PROXY, $this->proxyhost);
        }
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        if( substr($url, 0, 5) == 'https' ){
            curl_setopt( $ch , CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt( $ch , CURLOPT_SSL_VERIFYHOST, 0);
            if( $this->proxyhost ) curl_setopt( $ch, CURLOPT_HTTPPROXYTUNNEL, 1);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        return $this->handle = $ch;
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
            trim( $k );
            trim( $v );
            $headers[ $k ] = $v;
        }
        $body = substr( $curl_data, $curl_info['header_size']);
        $curl_info['headers'] = new \Gaia\Container($headers);
        $curl_info['response_header'] = $response_header;
        $curl_info['body'] = $body;
        $this->response = new \Gaia\Container( $curl_info );
        return $this->response;
    }
    
        
   /**
    * magic method.
    * @param string
    * @param mixed
    * return mixed
    */
    public function __set( $k, $v ){
        switch( $k ){
           case 'proxyhost':
                if( ! preg_match("/^[a-z][a-z0-9_\-\.\:]+$/i", $v)) return FALSE;
                return $this->__d[$k] = $v;
                
            case 'post' : 
                return $this->__d[$k] = is_array( $v ) ? http_build_query( $v ) : $v;    
            }
        return $this->__d[$k] = $v;
    }
}
// EOC