<?php
namespace Gaia\Job;
use Gaia\Job;
use Gaia\Debugger;
use Gaia\Http;
use Gaia\Time;

// +---------------------------------------------------------------------------+
// | This file is part of the Job Framework.                                   |
// | Author 72squared  (john@gaiaonline.com)                                   |
// +---------------------------------------------------------------------------+

/**
 * Job Runner Class.
 * Allows us to dequeue and run jobs from the queue in a non-blocking fashion.
 */
class Runner {

   /**
    * @type int     How many jobs processed simultaneously?
    */
    protected $max = 3;
    
   /**
    * How many jobs should we process before we shutdown?
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
    * @type array   list of job objects waiting
    */
    protected $queue = array();
    
   /**
    * @type bool    flag for if we want to keep running.
    */
    protected $active = TRUE;
    
   /**
    * @type int     how many jobs processed so far?
    */
    protected $processed = 0;
    
    /**
    * how many jobs dequeued?
    */
    protected $dequeued = 0;
    
   /**
    * @type int     how many failures?
    */
    protected $failed = 0;
    
   /**
    * @type int     how many no repies?
    */
    protected $noreplies = 0;

   /**
    * When did we last run the tasks?
    */
    protected $lastrun = 0;
    
   /**
    * @type int     debugger
    */
    protected $debug = NULL;
    
   /**
    * the http pool object.
    */
    protected $pool;
    
    /**
    * a list of callbacks to run every few seconds.
    */
    protected $tasks = array();
    
    /**
    * when dequeueing use this pattern to match which queues to watch.
    */
    protected $watch = '*';
    
    /**
    * class constructor. Optionally pass in an http pool object.
    */
    public function __construct( Http\Pool $pool = NULL ){
        $this->pool = ( $pool ) ? $pool : new Http\Pool;
        $runner = $this;
        $closure = function( Http\Request $job ) use ($runner ) { 
            return $runner->handle( $job ); 
        };
        $this->pool->attach( $closure );
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
        $this->watch = $pattern;
    }
    
    /**
    * start process the jobs in the queue.
    */
    public function process(){
        $this->active = TRUE;
        if( ! $this->start ) $this->start = Time::now();
        $this->populate();
        while( $this->active ){
            
            if( $this->limit && $this->dequeued >= $this->limit ){
                return $this->shutdown();
            }
        
            $time = Time::now();
    
            if( $this->timelimit && ($this->start + $this->timelimit) < $time ){
                return $this->shutdown();
            }
                
            if( ( $time - 2 ) >= $this->lastrun ) {
                 $this->debug('maintenance tasks');
                if( $this->debug) $this->debug('jobs running: ' . count( $this->pool->requests() ) );
        
                $this->lastrun = $time;
                try {
                    $this->populate();            
                    foreach( $this->tasks as $closure ) $closure( $this );
                    
                } catch( Exception $e ){
                    $this->debug( $e->__toString());
                }
            }
            if( $this->active && ! $this->pool->select(0.2) ) {
                usleep(200000);
                $this->populate();
            }
        }
    }
    
    /**
    * is the job runner still active?
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
            'running'=> count( $this->pool->requests() ),
            'queued' => count( $this->queue ),
            'processed'=>$this->processed,
            'failed'=>$this->failed,
            'noreplies'=>$this->noreplies,
        );
    }
    
    /**
    * add a job to the pool
    */
    public function addJob( Job $job, $opts = array() ){
        return $this->pool->add( $job, $opts );
    }
    
    /**
    * make sure we are watching the right queues.
    */
    protected function syncWatch($conn){
        $watch = $ignore = array();
        $config = Job::config();
        $block_patterns = array();
        foreach( $config->queueRates() as $pattern => $rate){
            if( $rate >= mt_rand(1, 100) ) continue;
            $block_patterns[] = $pattern;
        }
        $debug = $this->debug;
        $pattern = $this->buildTubePattern( $this->watch );
        //print "\n$pattern\n";
        $allow = function( $tube ) use ($pattern, $block_patterns, $debug ){
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
                    if( $debug ) call_user_func( $debug, "blocking $tube"); 
                    return FALSE;
                }
            }
            return TRUE;
        };
        
        foreach( $conn->listTubes() as $tube ) {
           
            if( $allow( $tube ) ) {
                $watch[] =  $tube;
            } else {
               $ignore[] = $tube; 
            }
        }
        if( count( $watch ) < 1 ) return FALSE;
        foreach( $watch as $tube ) $conn->watch( $tube );
        foreach( $ignore as $tube ) $conn->ignore( $tube );
        return TRUE;
    }
    
    
    
    /**
    * remove all of the jobs from a given queue.
    *
    */
    public function flush( $pattern = '*', $start = NULL, $end = NULL ){
        $tubes = $this->shardsByRange( $pattern, $start, $end );
        $ct = 0;
        $config = Job::config();
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
        $config = Job::config();
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
        return $this->flush( $pattern, NULL, Time::now() - Job::config()->ttl() );
    }
    
    
   /**
    * Set the max number of jobs to process simultaneously.
    * @param int
    * @return int
    */
    public function setMax( $v ){
        return $this->max = intval( $v );
    }
    
   /**
    * Set the limit number of jobs to process.
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
    * populate jobs into the system.
    * @return boolean
    */
    public function populate(){
        try {
            if( ! $this->active && ! $this->pool->requests() ){
                return TRUE;
            }
            
            if( $this->limit && $this->dequeued >= $this->limit ){
                return $this->shutdown();
            }
                        
            if( $this->active && count( $this->queue ) < $this->max ){
                $ct = $this->max;
                if( $this->debug) $this->debug('starting dequeue: '.count( $this->queue ));
                $config = Job::config();
                $conns = $config->connections();
                $keys = array_keys( $conns );
                shuffle( $keys ); 
                foreach( $keys as $key ){
                    $conn = $conns[ $key ];
                    if( ! $this->syncWatch( $conn ) ) continue;
                    while( $res = $conn->reserve(0) ){
                        $id = $conn->hostInfo() . '-' . $res->getId();
                        $job = new Job( @json_decode($res->getData(), TRUE) );
                        if( ! $job->url ) {
                            $conn->delete( $res );
                            continue;
                        }
                        $job->id = $id;
                        $this->queue[ $id ] = $job;
                        $this->dequeued++;
                        if( $this->limit && $this->dequeued >= $this->limit ) break 2;
                        if( $ct-- < 1 ) break 2;
                    }
                }                
                if( $this->debug) $this->debug('ending dequeue: ' . count( $this->queue ) );
            }
            
            while( count( $this->pool->requests() ) <  $this->max ){
                if( ! ( $job = array_shift( $this->queue ) ) ) break;
                if( ! $job instanceof Job ) {
                     $this->debug( $job );
                    continue;
                }
                try { 
                    $this->pool->add( $job );
                } catch( Exception $e ){
                     $this->debug( $e );
                }
            }
            return TRUE;
         } catch( Exception $e ){
            $this->debug( $e );
            return FALSE;
         }
    }

	/**
	 * don't allow any more jobs to be dequeued. start orderly shutdown.
	 */
	public function shutdown()
	{
		if( ! $this->active ) return;
		$this->active = FALSE;
		while( $job = array_pop( $this->queue ) ) $this->pool->add( $job );
		 $this->debug('calling http\pool::finish');
		$this->pool->finish();
		$this->debug('http\pool::finish done');
	}
	
	/**
	* handle a job that was run
	*/
	public function handle( Http\Request $job ){
	    $this->populate();
        if( ! $job instanceof Job ) return;
        if( $job->id ) $this->processed++;
        if( $job->response->http_code != 200 ) {
            if( $job->id ){
                ($job->response->http_code == 0 && $job->response->size_download == 0 ) ? 
                        $this->noreplies++ : $this->failed++;
            }
        }
	}
	
	/**
	* attach a debugger.
	*/
	public function attachDebugger( \Closure $debug ){
	    $this->debug = $debug;
	}
	
	protected function debug( $message ){
	    if( ! $call = $this->debug ) return;
	    return $call( $message );
	}
	
	protected function buildTubePattern( $v ){
	    $prefix = Job::config()->queuePrefix();
        return '#^' . preg_quote($prefix, '#') . '(' . str_replace('\*', '([^\n]+)?', preg_quote($v, '#')) . ')' . '_([\d]{8})$#';
	}
}
// EOC
