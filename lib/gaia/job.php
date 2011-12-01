<?php
namespace Gaia;
use Gaia\Exception;
use Gaia\Pheanstalk;
use Gaia\HTTP\Request;
use Gaia\Job\Config;

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
class Job extends Request {

    protected $id = NULL;
    protected $flag = FALSE;
    protected $delay = 0;
    protected $proxyhost = FALSE;
    protected $attempts = FALSE;
    protected $callback = '';
    protected $queue = '';
    protected $expires = NULL;
    protected $priority = 1000;
    protected $ttr = 30;
    protected $start = NULL;
                            
    protected static $config;
    
    public static function configure( Config $config ){
        return self::$config = $config;
    }
    
   /**
    * Store the job in the job queue
    * @return boolean
    */
    public function store(){
        $now = Time::now();
        $delay = $this->delay;
        $start = $this->start = $now + $delay;
        $config = self::config();
        if( ! isset( $this->expires) ) $this->expires = $start  + $config->ttl();
        $tube = $config->queuePrefix() . $this->queue . '_' . date('Ymd', $start );
        $try= $config->retries() + 1;
        $keys = FALSE;
        $conns = $config->connections();
        if( $id = $this->id ){
            list( $server ) = explode('-', $id, 2);
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
            $res = $conn->putInTube( $tube,  json_encode( (array) $this ), $this->priority, $delay, $this->ttr );
            if( ! $res ) {
                continue;
            }
            return $this->id = $conn->hostInfo() . '-' . $res;
        }
        throw new Exception('storage error', $conns);
    }
    
    public static function find( $key ){
        if( ! $key ) throw new Exception('invalid id', $key );
        list( $server, $id ) = explode('-', $key, 2);
        if( ! $server ) throw new Exception('invalid id', $key );
        $conns = self::config()->connections();
        if( ! isset( $conns[ $server ] ) ) throw new Exception('server not found', $key );
        $conn = $conns[ $server ];
        $res = $conn->peek( $id );
        if( ! $res ) throw new Exception('conn error', $conn );
        $job = new self( $res->getData() );
        if( ! $job->url ) {
            $conn->delete( $res );
            continue;
        }
        $job->id = $key;
        return $job;
    }
    
   /**
    * try to run the job right away.
    * @param int    how many seconds to wait on i/o before giving up
    * @return boolean
    */
    public function exec(array $opts = array()){
        if( ! isset( $opts[CURLOPT_CONNECTTIMEOUT] ) ) $opts[CURLOPT_CONNECTTIMEOUT] = 1;
        return parent::exec($opts);
    }
    
    public function run(array $opts = array()){
        $this->exec($opts);
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
        if( $this->expires < Time::now() ) return $this->remove();
        if( ! $this->id ) return FALSE;
        list( $server, $id ) = explode('-', $this->id, 2);
        if( ! $server ) return FALSE;
        $conns = self::config()->connections();
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
        $conns = self::config()->connections();
        if( ! isset( $conns[ $server ] ) ) return false;
        $conn = $conns[ $server ];
        $res = $conn->delete( new \Pheanstalk_Job($id, '') );
        if( ! $res ) throw new Exception('conn error', $conn );
        return $res;
    }

    public static function config(){
        if( ! self::$config ) self::$config = new Config;
        return self::$config;
    }
    
    
    
   /*******************           UTILITY METHODS BELOW           ************************/
    
   /**
    * utility method. send the Http request out through a stream and return the stream object
    * @param int    how many seconds to wait on networking I/O
    */
    public function build( array $opts = array() ){
        $opts[CURLOPT_TIMEOUT] = $this->ttr;
        if( ! isset($opts[CURLOPT_HTTPHEADER]) )$opts[CURLOPT_HTTPHEADER] = array();
        $opts[CURLOPT_FOLLOWLOCATION] = 1;
        if( substr($this->url, 0, 5) == 'https' ){
            $opts[CURLOPT_SSL_VERIFYPEER] = 0;
            $opts[CURLOPT_SSL_VERIFYHOST] = 0;
        }
        $closure = self::config()->builder();
        if( $closure ) {
            $closure($this, $opts);
        }
        $ch = parent::build($opts);
        return $ch;
    }
    
    public function handle(){
        $response = parent::handle();
        $closure = self::config()->handler();
        if( $closure ) {
            $closure( $this, $response );
        }
        if( $response->http_code == 200 ) {
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
        switch( $k ){
            case 'id' : 
                if( preg_match("/^[a-z_0-9\.]+:[0-9]+-[0-9]+$/", $v)){
                    return $this->$k = (string) $v;
                }
                return $this->$k = NULL;
                
            case 'flag':
                return $this->$k = ( $v ) ? TRUE : FALSE;
            
            case 'delay':
            case 'expires':
            case 'attempts': 
            case 'priority':
            case 'ttr':
                if( ! preg_match("/^[0-9]+$/", $v)) return FALSE;
                return $this->$k = $v;
                
            case 'queue' :
                if( ! ctype_alnum( $v ) ) return FALSE;
                return $this->$k = strtolower($v);
                
           case 'proxyhost':
                if( ! preg_match("/^[a-z][a-z0-9_\-\.\:]+$/i", $v)) return FALSE;
                return $this->$k = $v;
                
            case 'url' :
            case 'callback': 
                if( strlen( $v ) < 1 ) break;
                break;
                
        }
        return $this->$k = $v;
    }
    
    public function __get( $k ){
        return isset( $this->$k ) ? $this->$k : NULL;
    }
    
    public function __isset( $k ){
        return isset( $this->$k ) ? TRUE : FALSE;
    }
}
// EOC