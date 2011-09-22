<?php
namespace Gaia;
use Gaia\Exception;
use Gaia\Pheanstalk;
use Gaia\HTTP\Request;

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
class Job extends Request implements \Iterator {

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
    public function exec(array $opts = array(), array $headers = array()){
        if( ! isset( $opts[CURLOPT_CONNECTTIMEOUT] ) ) $opts[CURLOPT_CONNECTTIMEOUT] = 1;
        return parent::exec($opts, $headers);
    }
    
    public function run(array $opts = array(), array $headers = array()){
        $this->exec($opts, $headers);
        return (bool) $this->response->body;
    }
    
   /**
    * mark the job as complete
    */
    public function complete(){
        if( $this->flag ) return TRUE;
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
        $res = $conn->release( new \Pheanstalk_Job($id, ''), $this->priority, $this->ttr  + 300  );
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

    public static function config(){
        if( isset( self::$config ) ) return self::$config;
        return self::$config = new \Gaia\Container;
    }
    
    
    
   /*******************           UTILITY METHODS BELOW           ************************/
    
   /**
    * utility method. send the Http request out through a stream and return the stream object
    * @param int    how many seconds to wait on networking I/O
    */
    public function build( array $opts = array(), array $headers = array() ){
        $domain = self::config()->get('domain');
        if( ! $domain ) $domain = '127.0.0.1';
        if( substr( $this->url, 0, 1) == '/') $this->url = 'http://' . $domain . $this->url;
        if( $this->id ) $headers[] = 'job-id: ' . $this->id;
        $opts[CURLOPT_TIMEOUT] = $this->ttr;
        $ch = parent::build($opts, $headers);
        return $ch;
    }
    
    public function handle( $curl_data, $curl_info ){
        $response = parent::handle( $curl_data, $curl_info );
        if( $response->http_code == 200 ) {
            if($response->headers->{'gaia-job-id'}==$this->id) $this->flag = 1;
            $this->complete();
        } else {
            $this->fail();
        }
        return $response;
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
        return parent::__set( $k, $v );
    }
}
// EOC