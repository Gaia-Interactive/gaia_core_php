<?php
namespace Gaia\APN;
use Gaia\Time;
use Gaia\Stream;
use Gaia\Exception;

// +---------------------------------------------------------------------------+
// | This file is part of the APN Framework.                                   |
// | Author 72squared  (john@gaiaonline.com)                                   |
// +---------------------------------------------------------------------------+

/**
 * APN Runner Class.
 * Allows us to dequeue and run notices from the queue in a non-blocking fashion.
 */
class Runner {

    protected $streams = array();

   /**
    * @type int    how many bytes to send over the wire at a time per connection?
    */
    protected $max_bytes = 1024;
    
   /**
    * How many notices should we process before we shutdown?
    */
    protected $limit = 0;
    
    /**
    * how many seconds to run before shutting down.
    */
    protected $timelimit = 0;
    
    /**
    * keep track of the time we started.
    */
    protected $start = 0;
    
   /**
    * @type bool    flag for if we want to keep running.
    */
    protected $active = TRUE;
    
   /**
    * @type int     how many notices processed so far?
    */
    protected $processed = 0;
    
   /**
    * @type int     how many failures?
    */
    protected $failed = 0;  
    
   /**
    * @type int     how many invalid?
    */
    protected $invalid = 0;

   /**
    * When did we last run the tasks?
    */
    protected $lastrun = 0;
    
   /**
    * @type int     debugger
    */
    protected $debug = NULL;
    
   /**
    * the stream pool object.
    */
    protected $pool;
    
   /**
    * The closure for instantiating streams based on the app name.
    */
    protected $stream_builder;
    
    
    /**
    * a list of callbacks to run every few seconds.
    */
    protected $tasks = array();
    
    /**
    * when dequeueing use this pattern to match which queues to watch.
    */
    protected $watch;
    
    /**
    * class constructor. Optionally pass in an http pool object.
    */
    public function __construct( Stream\Pool $pool = NULL ){
        $this->pool = ( $pool ) ? $pool : new Stream\Pool;
        $this->watch('*');
    }
    
    /**
    * add a task.
    */
    public function addTask( \Closure $closure ){
        $this->tasks[] = $closure;
    }
    
    /**
    * specify a pattern for which queues to watch.
    */
    public function watch( $pattern ){
        $this->watch = $this->buildTubePattern( $pattern );
    }
    
    /**
    * start process the notices in the queue.
    */
    public function process(){
        $this->active = TRUE;
        if( ! $this->start ) $this->start = Time::now();
        
        while( $this->active ){
            
            if( $this->limit && $this->processed >= $this->limit ){
                return $this->shutdown();
            }
            $time = Time::now();
    
            if( $this->timelimit && ($this->start + $this->timelimit) < $time ){
                return $this->shutdown();
            }
            $this->populate();
            if( $this->tasks && ( $time - 2 ) >= $this->lastrun ) {
                $this->debug('running maintenance tasks', E_NOTICE);        
                $this->lastrun = $time;
                try {
                    foreach( $this->tasks as $closure ) $closure( $this );
                    
                } catch( Exception $e ){
                    $this->debug( $e->__toString(), E_ERROR);
                }
            }
            
            if( $this->active && ! $this->pool->select(0.2) ) {
                usleep(200000);
            }
        }
    }
    
    /**
    * is the runner still active?
    */
    public function isActive(){
        return $this->active;
    }

    
    
    /**
    * get a list of stats.
    */
    public function stats(){
        return array(
            'uptime'=> Time::now() - $this->start,
            'status'=> ($this->active ? 'running' : 'shutdown'),
            'processed'=>$this->processed,
            'invalid'=>$this->invalid,
            'failed'=>$this->failed,
        );
    }
    
    /**
    * make sure we are watching the right queues.
    */
    protected function buildAllow( array & $block_patterns ){
        $watch = $ignore = array();
        $config = Config::instance();
        foreach( $config->queueRates() as $pattern => $rate){
            if( $rate >= mt_rand(1, 100) ) continue;
            $block_patterns[$pattern] = $pattern;
        }
        $debug = $this->debug;
        $pattern = $this->watch;
        //print "\n$pattern\n";
        return function( $tube ) use ($pattern, & $block_patterns, $debug ){
            if( ! preg_match( $pattern, $tube, $match ) ) return FALSE;
            $queue = $match[1];
            $date = array_pop($match);
            $now = Time::now();
            $max_date = date('Ymd', $now);
            $min_date = date('Ymd', $now - (86400 * 3));        
            if( $date > $max_date ) return FALSE;
            if( $date < $min_date ) return FALSE;
            if( ! $block_patterns ) return TRUE;
            foreach( $block_patterns as $pattern ){
                if( fnmatch( $pattern, $queue ) ) {
                    return FALSE;
                }
            }
            return TRUE;
        };
    }
    
    protected function syncWatch( $conn, \Closure $allow ){
        $watch = $ignore = array();
        foreach( $conn->listTubes() as $tube ) {
           
            if( $allow( $tube ) ) {
                $watch[] =  $tube;
            } else {
               $ingore[] = $tube; 
            }
        }
        if( count( $watch ) < 1 ) return FALSE;
        if( $this->debug ) $this->debug('watching tubes: ' . implode(',', $watch ), E_NOTICE);
        foreach( $watch as $tube ) $conn->watch( $tube );
        foreach( $ignore as $tube ) $conn->ignore( $tube );
        return TRUE;
    }
        
    
    
    /**
    * remove all of the notices from a given queue.
    *
    */
    public function flush( $pattern = '*', $start = NULL, $end = NULL ){
        $tubes = $this->shardsByRange( $pattern, $start, $end );
        $ct = 0;
        $config = Config::instance();
        $conns = $config->connections();
        foreach( $conns as $conn ){
            foreach( $tubes as $tube ){
                foreach( array( 'buried', 'delayed', 'ready' ) as $type ){
                    $cmd = 'peek' . $type;
                    try {
                        while( $res = $conn->$cmd( $tube ) ) {
                            $conn->delete( $res );
                            $ct++;
                        }
                    } catch( \Exception $e ){}
                }
            }
        }
        return $ct;
    }
    
    public function shardsByRange( $pattern, $start = NULL, $end = NULL ){
        if( $end  && $end < $start ) $end = $start;
        $start = ( $start ) ? date('Ymd', strtotime($start) ) : '';
        $end = ( $end ) ? date('Ymd', strtotime($end) ) : '';
        $tubes = array();
        $config = Config::instance();
        $conns = $config->connections();
        $pattern = $this->buildTubePattern( $pattern );
        foreach( $conns as $conn ){
            foreach( $conn->listTubes() as $tube ) {
                if( preg_match($pattern, $tube, $match ) ) {
                    $date = array_pop($match);
                    if( $start && $start > $date ) continue;
                    if( $end && $date >= $end ) continue;
                    $tubes[ $tube ] = true;
                }
            }
        }
        return array_keys( $tubes );
    }
    
    public function flushOld( $pattern = '*' ){
        return $this->flush( $pattern, NULL, Time::now() - Config::instance()->ttl() );
    }
    
    
   /**
    * Set the max number of notices to process simultaneously per app.
    * @param int
    * @return int
    */
    public function setMaxByes( $v ){
        return $this->max_bytes = intval( $v );
    }
    
   /**
    * Set the limit number of notices to process.
    * @param int
    * @return int
    */
    public function setLimit( $v ){
        return $this->limit = intval( $v );
    }
    
    /**
    * how many secs do we want to run before quitting.
    * 0 means run forever.
    */
    public function setTimeLimit( $v ){
        return $this->timelimit = intval( $v );
    }
    
    /**
    * populate notices into the system.
    * @return boolean
    */
    public function populate(){
        try {
            if( ! $this->active && ! $this->pool->streams() ){
                return TRUE;
            }
            
            if( $this->limit && $this->processed >= $this->limit ){
                return $this->shutdown();
            }
            
            $queue_bytes = 0;
            
            $block_patterns = array();
            foreach( $this->pool->streams() as $stream ){
                if( strlen( $stream->out ) > $this->max_bytes ) $block_patterns[$stream->app] = $stream->app;
            }
                        
            $free_bytes = $this->max_bytes - $queue_bytes;
            if( $this->active && $free_bytes > 0 ){
                $ct = 0;
                $config = Config::instance();
                $conns = $config->connections();
                $keys = array_keys( $conns );
                shuffle( $keys ); 
                
                $block_patterns = array();
                
                $allow = $this->buildAllow( $block_patterns );
                
                foreach( $keys as $key ){
                    $conn = $conns[ $key ];
                    if( ! $this->syncWatch( $conn, $allow ) ) continue;
                    
                    while( $res = $conn->reserve(0) ){
                        $this->debug($res);
                        if( ! $this->syncWatch( $conn, $allow ) ) continue 2;
                        try {
                            $notice = new AppNotice($res->getData());
                        } catch( Exception $e ){
                            $this->invalid++;
                            if( $this->debug ) $this->debug($e, E_WARNING);
                            $conn->delete( $res );
                            continue;
                        }
                        if( ! $notice->getDeviceToken() || ! $notice->getApp() ) {
                            $this->invalid++;
                            if( $this->debug ) $this->debug( new Exception('invalid notice', $notice), E_WARNING);
                            $conn->delete( $res );
                            continue;
                        }
                        $stream = $this->connection( $notice->getApp() );
                        $stream->out .= $notice->core()->serialize();
                        if( $this->debug ) $this->debug($notice, E_NOTICE);
                        $this->processed++;
                        $ct++;
                        $conn->delete( $res );
                        if( strlen( $stream->out ) > $this->max_bytes ) {
                            $block_patterns[$stream->app] = $stream->app;
                        }
                        if( $this->limit && $this->processed >= $this->limit ) break 2;
                    }
                }                
                if( $this->debug && $ct > 0) $this->debug("notices dequeued: $ct", E_NOTICE);
                if( $ct < 1 ){
                   $pending = FALSE;
                   foreach( $this->pool->streams() as $stream ){
                        if( $stream->out ) {
                            $pending = TRUE;
                            break;
                        }
                   }
                   if( ! $pending ){
                        if( $this->debug) $this->debug("nothing to process. waiting ...", E_NOTICE);
                        sleep(1);
                   }
                }
            }
            return TRUE;
         } catch( Exception $e ){
            $this->debug( $e, E_WARNING );
            return FALSE;
         }
    }
    
    
    protected function connection( $name ){
        if( isset( $this->streams[ $name ] ) ) return $this->streams[ $name ];
        if( ! $builder = $this->stream_builder ) {
            throw new Exception('no stream builder');
        }
        $stream = $builder( $name );
        if( ! $stream instanceof Connection ) {
            throw new Exception( 'invalid connection');
        }
        $stream->app = $name;
        $read = $stream->read;
        $runner = $this;
        
        $this->streams[ $name ] = $stream;
        $this->pool->add( $stream );
        return $stream;
    }
    
    public function attachStreamBuilder( \Closure $c ){
        $this->stream_builder = $c;
    }

	/**
	 * don't allow any more notices to be processed. start orderly shutdown.
	 */
	public function shutdown()
	{
		if( ! $this->active ) return;
		$this->active = FALSE;
		$this->debug('initiating shutdown sequence ...', E_NOTICE);
		while ($this->pool->select(1) === TRUE) { 
		    foreach( $this->pool->streams() as $stream ){
		        foreach( $stream->readResponses() as $response ){
                    $this->failed++;
                    $this->debug( $response );
                }
		        if( $stream->in == '' && $stream->out == '' ){
		            $this->pool->remove( $stream );
		        }
		    }
		}
		$this->debug('shutdown completed');
	}

	/**
	* attach a debugger.
	*/
	public function attachDebugger( \Closure $debug ){
	    $this->debug = $debug;
	   // $this->pool->attachDebugger( $debug );
	}
	
	public function debug( $message, $level = E_NOTICE ){
	    if( ! $call = $this->debug ) return;
	    return $call( $message, $level );
	}
	
	protected function buildTubePattern( $v ){
	    $prefix = Config::instance()->queuePrefix();
        return '#^' . preg_quote($prefix . Queue::SEP, '#') . '(' . str_replace('\*', '([^\n]+)?', preg_quote($v . Queue::SEP, '#')) . ')' . '([\d]{8})$#';
	}
}
// EOC
