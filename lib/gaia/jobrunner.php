<?php
namespace Gaia;
use Gaia\Job;

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
    
    protected $timelimit = 0;
    
    protected $start = 0;
    
    protected $registering = FALSE;
    
    protected $callbacks = array();
    
   /**
    * @type array   list of running jobs
    */
    protected $jobs = array();
    
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
    * @type int     debug stream
    */
    protected $debug = NULL;
    
   /**
    * keep track of the debug level.
    *
    */
    protected $debug_level = 1;
    
   /**
    * Make sure we should be dequeueing.
    */
    protected $dequeue = TRUE;
    
    
    public function send(){
        $this->start = time();
        $this->populate();
        while( $this->alive ){
            if( $this->debug && $this->debug_level > 1 ) $this->debug('starting socket select');
            if(! $this->select(1)) sleep(1);
            if( $this->debug && $this->debug_level > 1 ) $this->debug('ending socket select');
        }
    }
    
    public function attach( $callback ){
        if( is_callable( $callback ) ) $this->callbacks[] = $callback;
    }

    
    /**
    * run all the tasks that need to be done every few seconds.
    *
    *
    */
    public function runTasks(){
        $time = time();
        if( ( $time - 2 ) < $this->lastrun ) return;
        if( $this->debug && $this->debug_level > 1 ) $this->debug('maintenance tasks');
        if( $this->debug ) $this->debug('jobs running: ' . count( $this->jobs ) );

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
                if( count( $this->jobs ) > 0 || !$this->dequeue ) break;
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
        $handle = $job->buildRequest();
        $this->jobs[(int)$handle] = $job;
        curl_multi_add_handle($this->_handle, $handle);
    }
    
    protected static function hostinfo(){
        if( isset( self::$hostid ) ) return self::$hostid;
        $ifconfig = @shell_exec('/sbin/ifconfig');
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
    
    public function setTimeLimit( $v ){
        return $this->timelimit = intval( $v );
    }
    
    /**
    * populate jobs into the system.
    * @return boolean
    */
    public function populate(){
        try {
            if( ! $this->alive && count( $this->jobs ) == 0 ){
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
            
            while( count( $this->jobs ) <  $this->max ){
                if( ! ( $job = array_shift( $this->queue ) ) ) break;
                if( ! $job instanceof Job ) {
                    if( $this->debug ) $this->debug( $job );
                    continue;
                }
                try { 
                    $handle = $job->buildRequest();
		            $this->jobs[(int)$handle] = $job;
		            curl_multi_add_handle($this->_handle, $handle);

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
    * display the outcome of a job
    * @return bool
    */
    public function displayJobOutcome( Job $job ){
        if( ! $this->debug ) return;
        $out = "\nJOB";
        if( $job->id ) $out .=" - " . $job->id;
        $info = $job->curl_info;
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
    * instantiate a new job
    */
    protected function job( $url ){
        return new Job( $url );
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

	/**
	 * The curl multi handle.
	 * @var handle $_handle
	 */
	protected $_handle = NULL;

	/**
	 * Initializes the curl multi request.
	 */
	public function __construct()
	{
		$this->_handle = curl_multi_init();
	}
	
	public function __destruct(){
        curl_multi_close($this->_handle);
	}

	/**
	 * Cleans up the curl multi request
	 *
	 * If individual curl requests were not completed, they will be closed through curl_close()
	 */
	public function shutdown()
	{
		if( ! $this->alive ) return;
        if( $this->debug ) $this->debug('shutting down');
		$this->alive = FALSE;
		$this->registering = FALSE;
		$this->register();
		$this->finish();
		foreach ($this->jobs as $job){
		    if( ! $job->handle ) continue;
			curl_multi_remove_handle($this->_handle, $job->handle);
			curl_close($job->handle);
		}
	}

	/**
	 * Polls (non-blocking) the curl requests for additional data.
	 *
	 * This function must be called periodically while processing other data.  This function is non-blocking
	 * and will return if there is no data ready for processing on any of the internal curl handles.
	 *
	 * @return boolean TRUE if there are transfers still running or FALSE if there is nothing left to do.
	 */
	protected function poll()
	{
		$still_running = 0; // number of requests still running.

		do
		{
			$result = curl_multi_exec($this->_handle, $still_running);

			if ($result == CURLM_OK)
			{
				do
				{
					$messages_in_queue = 0;
					$info = curl_multi_info_read($this->_handle, $messages_in_queue);
					if ($info && isset($info['handle']) && isset($this->jobs[(int)$info['handle']]))
					{
						$curl_data = curl_multi_getcontent($info['handle']);
						$curl_info = curl_getinfo($info['handle']);
                        curl_multi_remove_handle($this->_handle, $info['handle']);
						curl_close($info['handle']);
                        $job = $this->jobs[ (int) $info['handle'] ];
                        unset( $this->jobs[ (int) $info['handle'] ] );
                        $this->populate();
                        if( ! $job instanceof Job ) continue;
                        if( $job->id ) $this->processed++;
                        $job->handleResponse( $curl_data, $curl_info );
                        if( $job->task == 'register' ) $this->registering = FALSE;
                        $this->displayJobOutcome( $job );
                        if( $job->curl_info->http_code == 200 ) {
                            if($job->curl_info->headers->{'gaia-job-id'}==$job->id) $job->flag = 1;
                            $job->complete();
                            
                        } else {
                            if( $job->task == 'refreshconfig'){
                                $this->shutdown();
                            }
                            
                            $job->fail();
                            if( $job->id ) ($job->curl_info->http_code == 0 && $job->curl_info->size_download == 0 ) ? $this->noreplies++ : $this->failed++;
                        }
                        return TRUE;
					}
				}
				while($messages_in_queue > 0);
			}
		}
		while ($result == CURLM_CALL_MULTI_PERFORM && $still_running > 0);

		// don't trust $still_running, as user may have added more urls
		// in callbacks
		return (boolean)$this->jobs;
	}
    
	protected function select($timeout = 1.0){
	    $this->runTasks();
		$result = $this->poll();

		if ($result)
		{
			curl_multi_select($this->_handle, $timeout);
			$result = $this->poll();
		}

		return $result;
	}
	
	protected function finish(){
		while ($this->select() === TRUE) { /* no op */ }

		return TRUE;
	}
    
}
// EOC
