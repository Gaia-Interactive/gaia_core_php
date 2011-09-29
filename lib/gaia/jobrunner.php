<?php
namespace Gaia;
use Gaia\Job;
use Gaia\Http;

// +---------------------------------------------------------------------------+
// | This file is part of the Job Framework.                                   |
// | Author 72squared  (john@gaiaonline.com)                                   |
// +---------------------------------------------------------------------------+

/**
 * Job Runner Class.
 * Allows us to dequeue and run jobs from the queue in a non-blocking fashion.
 */
class JobRunner {

    protected static $hostinfo;

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
    * keep track of whether or not we are in the middle of sending an http request
    * to register via the callback url.
    */
    protected $registering = FALSE;
    
   /**
    * @type array   list of job objects waiting
    */
    protected $queue = array();
    
   /**
    * @type bool    flag for if we want to keep running.
    */
    protected $alive = TRUE;
    
   /**
    * @type int     how many jobs processed so far?
    */
    protected $processed = 0;
    
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
    * Make sure we should be dequeueing.
    */
    protected $dequeue = TRUE;
    
   /**
    * @type int     debug stream
    */
    protected $debug = NULL;
    
   /**
    * keep track of the debug level.
    */
    protected $debug_level = 1;
    
   /**
    * the http pool object.
    */
    protected $pool;
    
    /**
    * class constructor. Optionally pass in an http pool object.
    */
    public function __construct( Http\Pool $pool = NULL ){
        $this->pool = ( $pool ) ? $pool : new Http\Pool;
        $this->pool->attach( array( $this, 'handle' ) );
    }
    
    
    
    public function send(){
        $this->start = time();
        $this->populate();
        while( $this->alive ){
            $this->runTasks();
            if(! $this->pool->select(1)) sleep(1);
            if( $this->debug && $this->debug_level > 1 ) $this->debug('ending socket select');
        }
    }

    
    /**
    * run all the tasks that need to be done every few seconds.
    * mainly, populate new jobs, attach a register callback, any other callbacks.
    */
    public function runTasks(){
        $time = time();
        if( ( $time - 2 ) < $this->lastrun ) return;
        if( $this->debug && $this->debug_level > 1 ) $this->debug('maintenance tasks');
        if( $this->debug &&  $this->debug_level > 1 ) $this->debug('jobs running: ' . count( $this->pool->requests() ) );

        $this->checkIfDisabled();
        $this->register();
        static $dbtime;
        if( $dbtime < 1 ) $dbtime = $time;
        $this->lastrun = $time;
        try {
            $this->dequeue = TRUE;
            if( $this->limit > 0 && $this->processed > $this->limit ){
                return $this->shutdown();
            }
            
            if( $this->timelimit && $this->start + $this->timelimit < $time ){
                return $this->shutdown();
            }
            
            
            for( $i = 0; $i < 10; $i++){
                $this->populate();
                if( count( $this->pool->requests() ) > 0 || !$this->dequeue ) break;
            }
            $this->register();
            if( ($time - 60)  > $dbtime ) {
                if( $this->debug && $this->debug_level > 1 ) $this->debug('repopulating settings');
                $this->refresh();
                $dbtime = $time;
            }
            
        } catch( Exception $e ){
            $this->debug( $e->__toString());
        }
    }
    
    protected function checkIfDisabled(){
        if( Job::config()->get('runner_disabled') ) $this->shutdown();
    }
    
    public function refresh(){
        foreach( $this->callbacks as $cb ) call_user_func( $cb, $this );
    }
    
    public function register(){
        if( $this->registering ) return;
        $this->registering = TRUE;
        $register_url = Job::config()->get('register_url');
        if( ! $register_url ) return;
        $job = new Job( $register_url );
        $job->ttr = 2;
        $job->task = 'register';        
        $job->post = array(
            'uptime'=> time() - $this->start,
            'status'=> ($this->alive ? 'running' : 'shutdown'),
            'processed'=>$this->processed,
            'failed'=>$this->failed,
            'noreplies'=>$this->noreplies,
        ) + self::hostInfo();
        $this->pool->add( $job, array(CURLOPT_CONNECTTIMEOUT=>1, CURLOPT_HTTPHEADER =>array('Connection: Keep-Alive','Keep-Alive: 300')) );
    }
    
    protected static function hostinfo(){
        if( isset( self::$hostinfo ) ) return self::$hostinfo;
        $ifconfig = @shell_exec('/sbin/ifconfig');
        $ip = '';
        if( preg_match('/inet addr:((?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3})/', $ifconfig, $matches) ) {
            $ip = $matches[1];
        }
        
        $hostname = trim(@shell_exec('/bin/hostname -f'));
        
        if( function_exists('posix_getpid') ) {
            $pid = posix_getpid();
        } else {
            $pid = trim(@shell_exec('ps -f | awk -v pid="$$" \' $2 == pid { print $3 } \''));
        }
        return self::$hostinfo = array('ip' => $ip, 'pid'=> $pid, 'hostname'=>$hostname);
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
            if( ! $this->alive && count( $this->pool->requests() ) == 0 ){
                return TRUE;
            }
            
            if( $this->alive && $this->dequeue && count( $this->queue ) < $this->max ){
                $amt = $this->max + 10;
                if( $amt < 20 ) $amt = 20;
                if( $this->debug && $this->debug_level > 1 ) $this->debug('starting dequeue: '.count( $this->queue ));
                foreach( Job::dequeue($amt) as $job ) $this->queue[] = $job;
                if( $this->debug && $this->debug_level > 1 ) $this->debug('ending dequeue: ' . count( $this->queue ) );

                if( count( $this->queue ) < $this->max ) $this->dequeue = FALSE;
            }
            
            while( count( $this->pool->requests() ) <  $this->max ){
                if( ! ( $job = array_shift( $this->queue ) ) ) break;
                if( ! $job instanceof Job ) {
                    if( $this->debug ) $this->debug( $job );
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
		if( ! $this->alive ) return;
		$this->alive = FALSE;
		$this->registering = FALSE;
		$this->register();
		$this->pool->finish();
	}
	
	public function handle( Http\Request $job ){
	    $this->populate();
	    $this->displayOutcome( $job );
        if( ! $job instanceof Job ) return;
        if( $job->id ) $this->processed++;
        if( $job->task == 'register' ) $this->registering = FALSE;
        if( $job->response->http_code != 200 ) {
            if( $job->task == 'refreshconfig'){
                $this->shutdown();
            }
            if( $job->id ) ($job->response->http_code == 0 && $job->response->size_download == 0 ) ? $this->noreplies++ : $this->failed++;
        }
	}
	
   /**
    * display the outcome of a http
    * @return bool
    */
    public function displayOutcome( Http\Request $request ){
        if( ! $this->debug ) return;
        $out = "\nHTTP";
        if( $request->id ) $out .=" - " . $request->id;
        $info = $request->response;
        if( $info->http_code != 200 ) $out .= '-ERR';
        $out .= ": " . $info->url;
        if( $this->debug_level < 2 && $info->http_code  == 200  ) return $this->debug( $out );
        if(  strlen( $info->response_header ) < 1 ) {
            $out .= " - NO RESPONSE";
        } else {
            $out .= "\n------\n";
            $out .= "\n" . $info->request_header;
            $out .= "\n\n" . $info->response_header . $info->body;
            $out .= "\n------\n";
        }
        $this->debug( $out );
        
    }
    
   /**
    * turn on debug output.
    * @return void
    */
    public function enableDebug( $debug = NULL ){
        if( ! $debug ) $debug = STDIN;
        if( is_resource( $debug ) ) return $this->debug = $debug;
        if( $fp = fopen( $debug, 'w' ) ) return $this->debug = $fp;
        return FALSE;
    }
    
    public function setDebugLevel( $level = 1 ){
        return $this->debug_level = intval( $level );
    }
    
    
   /**
    * turn off debug output.
    * @return void
    */
    public function disableDebug(){
        if( ! $this->debug ) return;
        $this->debug = NULL;
    }
    
   /**
    * print out a line of debug. not sure if this should be public or not.
    */
    public function debug( $v ){
        if( ! $this->debug ) return;
        fwrite( $this->debug, $this->renderDebugOutput($v) );
    }
    
   /**
    * build the rendered string
    */
    public function renderDebugOutput( $v ){
        if( $v instanceof Exception ) $v = $v->__toString();
        if( ! is_scalar( $v ) ) strval( $v );
        $dt =  "\n[" . date('H:i:s') . '] ';
        return $dt . str_replace("\n", $dt, trim( $v ));
    }
    
    
    
}
// EOC
