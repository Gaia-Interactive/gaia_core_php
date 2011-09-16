<?php
namespace Gaia;
use Gaia\Exception;
use Gaia\Pheanstalk;

/**
 * @package Unknown
 * @copyright 2003-present GAIA Interactive, Inc.
 * @license private/license/Unknown.txt
 */
// +---------------------------------------------------------------------------+
// | This file is part of the Job Framework.                                   |
// | Author 72squared  (john@gaiaonline.com)                                   |
// +---------------------------------------------------------------------------+

/**
 * The job class allows us to load an api call as a url, and either run it, or schedule
 * it to be run later. if i try to run it right now and the call fails, it will automatically
 * be stored for later.
 * 
 * store a job in the queue for later.
 *   $job = new Job($url);
 *   $job->store();
 *
 * run a job right away ( if it fails, it will be auto scheduled for later )
 *   $job = new Job($url);
 *   $job->run();
 *   
 * This examples above are pretty basic, but you can do more complex scheduling. 
 * The job system supports:
 *      scheduling a job for a given time window
 *      specifying how long to wait until trying the job again after a failure
 *      making sure that the same job isn't duplicated in the queue
 *      specifying post data as well as a url.
 *
 * schedule a job to be kicked off at a given seconds from now.
 *   $job = new job($url);
 *   $job->delay = 300;
 *   $job->store();
 *   
 * Can also schedule the time by passing it as the second arg to the constructor
 *   $job = new Job( $url, $unix_timestamp );
 *   $job->store();
 *
 * schedule how many attempts to run the job before flushing it to the dead queue.
 *   $job = new Job($url);
 *   $job->store();
 *
 * tell how long to wait after a failed attempt before trying again.
 *   $job = new Job($url);
 *   $job->timeout = 30; // 30 seconds.
 *   $job->store();
 *
 * specify complex url
 *   $job = new Job('https://username:password@domain.com:8080/path/to/file?querystring');
 *   $job->store();
 *
 * store an HTTP POST job
 *   $job = new Job( $url );
 *   $job->post = array('field1'=>'value1', 'field2'=>'value2');
 *   $job->store();
 *
 * store an HTTP POST with raw header info
 *   $job = new Job( $url );
 *   $job->post = '<body><test>1</test></body>';
 *   $job->store();
 */
class Job extends Container implements \Iterator {

   /**
    * Internal variables.
    *
    */
    protected $__d = array(
                    'id'=>NULL,
                    'flag'=>FALSE,
                    'url'=>'',
                    'post'=>'',
                    'delay'=>0,
                    'response'=>NULL,
                    'proxyhost'=>FALSE,
                    'attempts'=>0,
                    'callback'=>'',
                    'queue'=>'',
                    'expires'=>NULL,
                    'priority'=>1000,
                    'ttr'=>30,
                    'start'=>NULL,
    );
    
    protected static $connections = array();
    protected static $nextcheck = 0;
    protected static $callback;
    protected static $config;
    
    public static function attach( $callback ){
        if( is_callable( $callback ) ) self::$callback = $callback;
    }
    
    public static function connections(){
        $time = time();
        if( self::$nextcheck >= $time ) return self::$connections;
        self::$nextcheck = $time + 60;
        if( ! isset( self::$callback ) ) return self::$connections;
        $res = call_user_func( self::$callback );
        if( ! is_array( $res  ) ) return self::$connections;
        self::$connections = array();
        foreach( $res as $connection ){
            if( !  $connection instanceof Pheanstalk ) continue;
            self::$connections[$connection->hostInfo()] = $connection;
        }
        return self::$connections;
    }
    
   /**
    * Class constructor.
    * pass in a url and timestamp of the job to be called
    * @param string     URL
    * @param int        Unix timestamp (optional)
    */
    public function __construct($data = NULL, $delay = NULL ){
        $this->delay = $delay;
        parent::__construct( $data );
        if( substr( $this->url, 0, 1) == '/' && ! self::config()->isEmpty('default_domain')) 
            $this->url ='http://' . self::config()->get('default_domain') . $this->url;
	    if( ! $this->proxyhost ) $this->proxyhost = self::config()->get('default_proxyhost');
    }
    
   /**
    * Store the job in the job queue
    * @return boolean
    */
    public function store(){
        $delay = $this->delay;
        $this->start = time() + $delay;
        if( ! $this->expires ) $this->expires = $this->start  + 300;
        $tube = 'gaia_job_' . $this->queue;
        $try= self::config()->get('try') + 1;
        $keys = FALSE;
        $conns = self::connections();
        if( $this->id ){
            list( $server ) = explode('-', $this->id, 2);
            if( isset( $conns[ $server ] ) ){
                $keys = array( $server );
                foreach( array_keys( $conns ) as $k ){
                    if( $k == $server ) continue;
                    $keys[] = $k;
                }
            }
        }
        if( ! $keys ){
            $keys = array_keys( $conns );
            shuffle( $keys ); 
        }
        
        foreach( $keys as $key ){
            $conn = $conns[ $key ];
            if( ! $try-- ) break;
            $res = $conn->putInTube( $tube, $this->beanstalkExport(), $this->priority, $delay, $this->ttr );
            if( ! $res ) {
                continue;
            }
            return $this->__d['id'] = $conn->hostInfo() . '-' . $res;
        }
        throw new Exception('storage error', $conns);
    }
    
    
    public static function watch( $pattern = '*' ){
        $watch = $ignore = array();
        $conns = self::connections();
        foreach( $conns as $conn ){
            foreach( $conn->listTubes() as $tube ) {
                if( fnmatch('gaia_job_' . $pattern, $tube) ) {
                    $watch[ $tube ] = true;
                } else {
                   $ingore[ $tube ] = true; 
                }
            }
        }
        
        if( count( $watch ) < 1 ) return FALSE;
        
        foreach( $conns as $conn ){
            foreach( array_keys( $watch ) as $tube ) $conn->watch( $tube );
            foreach( array_keys( $ignore ) as $tube ) $conn->ignore( $tube );
        }
        return TRUE;
    }
    
    public static function dequeue( $ct = 10 ){
        $list = array();
        $conns = self::connections();
        $keys = array_keys( $conns );
        shuffle( $keys ); 
        foreach( $keys as $key ){
            $conn = $conns[ $key ];
            while( $res = $conn->reserve(0) ){
                $id = $conn->hostInfo() . '-' . $res->getId();
                $job = new self( $res->getData() );
                if( ! $job->url ) {
                    $conn->delete( $res );
                    continue;
                }
                $job->id = $id;
                $list[ $id ] = $job;
                if( $ct-- < 1 ) break 2;
            }
        }
        return $list;
    }
    
    
    
    
    
    protected function beanstalkExport(){
        $vars = array();
        foreach( array( 'url', 'post', 'queue', 'proxyhost', 'callback', 'attempts', 'priority', 'expires', 'ttr', 'start') as $k ){
            $vars[ $k ] = $this->__d[ $k ];
        }
        return json_encode( $vars );
    }
    
   /**
    * try to run the job right away.
    * @param int    how many seconds to wait on i/o before giving up
    * @return boolean
    */
    public function run(){
        $ch = $this->buildRequest( array('connecttimeout'=>1) );
        $this->handleResponse( $data = curl_exec($ch), curl_getinfo($ch));
        return (bool) $data;
    }
    
   /**
    * mark the job as complete
    */
    public function complete(){
        if( ! $this->remove() ) return FALSE;
        $this->flag = 1;
        return TRUE;
    }
    
   /**
    * mark the job as failed
    */
    public function fail(){
        if( $this->expires < time() ) return $this->remove();
        if( ! $this->id ) return FALSE;
        list( $server, $id ) = explode('-', $this->id, 2);
        if( ! $server ) return FALSE;
        $conns = self::connections();
        if( ! isset( $conns[ $server ] ) ) return false;
        $conn = $conns[ $server ];
        $res = $conn->release( new \Pheanstalk_Job($id, ''), $job->priority, $job->ttr  + 300  );
        if( ! $res ) throw new Exception('conn error', $conn );
        return $res;
    }
    
   /**
    * remove the job
    */
    public function remove(){
        if( ! $this->id ) return FALSE;
        list( $server, $id ) = explode('-', $this->id, 2);
        if( ! $server ) return FALSE;
        $conns = self::connections();
        if( ! isset( $conns[ $server ] ) ) return false;
        $conn = $conns[ $server ];
        $res = $conn->delete( new \Pheanstalk_Job($id, '') );
        if( ! $res ) throw new Exception('conn error', $conn );
        return $res;
    }
    
   /**
    * import job data from a different job class into this one.
    * @param mixed      either an array or Job object
    * @return void
    */
    public function load( $job ){
        if( is_string( $job ) && function_exists('json_decode') && ( $v = @json_decode( $job, TRUE ) ) ) $job = $v;
        if( is_object( $job ) ){
            foreach( array_keys($this->__d ) as $k ) $this->$k = ( isset( $job->$k ) ) ? $job->$k : NULL;
        }elseif( is_array( $job ) ){
            foreach(  array_keys($this->__d ) as $k ) $this->$k = ( isset( $job[$k] ) ) ? $job[$k] : NULL;
        } elseif( is_string( $job ) ){
            $this->url = $job;
        }
    }
    
    public static function config(){
        if( isset( self::$config ) ) return self::$config;
        return self::$config = new \Gaia\Container;
    }
    
    
    
   /*******************           UTILITY METHODS BELOW           ************************/
    
   /**
    * utility method. send the Http request out through a stream and return the stream object
    * @param int    how many seconds to wait on networking I/O
    */
    public function buildRequest(){
        $domain = self::config()->get('domain');
        if( ! $domain ) $domain = '127.0.0.1';
        $url = substr( $this->url, 0, 1) == '/'  ? 'http://' . $domain . $this->url : $this->url;
        
        $parts = @parse_url( $url );
        if( ! is_array( $parts ) ) $parts = array();
        $uri = isset( $parts['path'] ) ? $parts['path'] : '/';
        if( isset( $parts['query'] ) ) $uri .= '?' . $parts['query'];
        if( ! isset( $parts['host'] ) ) throw new Exception('invalid-uri');
        $ch = curl_init($url);
        $headers = array(
                    'Connection: Keep-Alive',
                    'Keep-Alive: 300',
                    'Accept-Charset: ISO-8859-1,utf-8',
                    'Accept-language: en-us',
                    'Accept: text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,image/jpeg,image/gif,*/*',
        );
        
        if( isset( $parts['user'] ) && isset( $parts['pass'] ) ){
            curl_setopt($ch , CURLOPT_USERPWD,$parts['user'].':'.$parts['pass']);
        }

        if( $this->id ) $headers[] = 'job-id: ' . $this->id;
        //$request->addHeaders(array('job-nonce'=>self::createNonce($uri) ) );
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
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->ttr);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
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
    
   /**
    * utility method.
    * Url encode the data to be stored.
    * @param mixed
    * @return string.
    */
    protected function urlencode($data) {
        if ( is_scalar($data) ) return $data;
        if( ! is_array( $data ) || count( $data ) < 1 ) return '';
        $querystring = '';
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                foreach ($val as $i=>$val2) $querystring .= urlencode($key).'[' . urlencode( $i ) . ']='.urlencode($val2).'&';
            } else {
                $querystring .= urlencode($key).'='.urlencode($val).'&';
            }
        }
        return substr($querystring, 0, -1);
    }
    
   /**
    * magic method.
    * @param string
    * @param mixed
    * return mixed
    */
    public function __set( $k, $v ){
        //if( ! array_key_exists( $k, $this->__d ) )  return FALSE;
        switch( $k ){
            case 'id' : 
                if( preg_match("/^[a-z_0-9\.]+:[0-9]+-[0-9]+$/", $v)){
                    return $this->__d[$k] = (string) $v;
                }
                return $this->__d[$k] = NULL;
                
            case 'flag':
                return $this->__d[$k] = ( $v ) ? TRUE : FALSE;
                
           case 'proxyhost':
                if( ! preg_match("/^[a-z][a-z0-9_\-\.\:]+$/i", $v)) return FALSE;
                return $this->__d[$k] = $v;
                
            case 'post' : 
                return $this->__d[$k] = $this->urlencode( $v );
            
            case 'delay':
            case 'expires':
            case 'attempts': 
            case 'priority':
            case 'ttr':
                if( ! preg_match("/^[0-9]+$/", $v)) return FALSE;
                return $this->__d[$k] = $v;
                
            case 'queue' :
                if( ! ctype_alnum( $v ) ) return FALSE;
                return $this->__d[$k] = strtolower($v);
                
            case 'url' :
            case 'callback': 
                if( strlen( $v ) < 1 ) break;
                if( !self::config()->isEmpty('url_prefix' ) ) {
                    $parts  = @parse_url( $v );
                    if( is_array($parts) && !isset( $parts['host'] ) ) {
                        $v = self::config()->get('url_prefix' ) . $v;
                    }
                }
                break;
                
            }
        return $this->__d[$k] = $v;
    }
    
    public function handleResponse( $curl_data, $curl_info ){  
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
        $this->curl_info = new \Gaia\Container( $curl_info );
    }
}
// EOC